<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { FormField, HandlesValidationErrors } from 'laravel-nova'

export default {
  mixins: [FormField, HandlesValidationErrors],

  props: ['resourceName', 'resourceId', 'field'],

  setup(props) {
    const search = ref('')
    const showDropdown = ref(false)
    const selectedItems = ref([])
    const loading = ref(false)
    const showCreateModal = ref(false)

    // Computed
    const filteredOptions = computed(() => {
      if (!props.field.options) return []

      return props.field.options.filter(option =>
          option.label.toLowerCase().includes(search.value.toLowerCase()) &&
          !selectedItems.value.some(selected => selected.value === option.value)
      )
    })

    const hasSelectableOptions = computed(() => {
      return filteredOptions.value.length > 0
    })

    // Methods
    const selectItem = (option) => {
      selectedItems.value.push(option)
      search.value = ''
      showDropdown.value = false
    }

    const selectAllFiltered = () => {
      if (filteredOptions.value.length) {
        selectedItems.value = [
          ...selectedItems.value,
          ...filteredOptions.value
        ]
        search.value = ''
        showDropdown.value = false
      }
    }

    const removeItem = (option) => {
      selectedItems.value = selectedItems.value.filter(item => item.value !== option.value)
    }

    const handleClickOutside = (event) => {
      if (!event.target.closest('.tw-relative')) {
        showDropdown.value = false
      }
    }

    const handleResourceCreated = (resource) => {
      const newOption = {
        value: resource.id.toString(),
        label: resource.title || `ID: ${resource.id}`
      }

      props.field.options = [...(props.field.options || []), newOption]
      selectItem(newOption)
      showCreateModal.value = false
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)

      if (props.field.value) {
        selectedItems.value = props.field.value
            .map(value => {
              const option = props.field.options?.find(opt => opt.value === value)
              return option || { value, label: `ID: ${value}` }
            })
            .filter(Boolean)
      }
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      search,
      showDropdown,
      selectedItems,
      loading,
      showCreateModal,
      filteredOptions,
      hasSelectableOptions,
      selectItem,
      selectAllFiltered,
      removeItem,
      handleClickOutside,
      handleResourceCreated,
    }
  },

  methods: {
    fill(formData) {
      formData.append(
          this.field.attribute,
          JSON.stringify(this.selectedItems.map(item => item.value))
      )
    },
  }
}
</script>

<template>
  <DefaultField :field="field" :errors="errors">
    <template #field>
      <div class="tw-relative hasManySearchable">
        <div class="flex flex-col space-y-2">
          <div v-if="field.showCreateButton" class="mb-2">
            <button
                type="button"
                @click="showCreateModal = true"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-500 rounded-lg hover:bg-primary-600"
            >
              {{ field.createButtonLabel }}
            </button>

            <CreateRelationModal
                :show="showCreateModal"
                :resource-name="field.resourceName"
                @create-cancelled="showCreateModal = false"
                @set-resource="handleResourceCreated"
            />
          </div>

          <div class="flex flex-wrap p-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900">
            <div v-for="selected in selectedItems"
                 :key="selected.value"
                 class="inline-flex items-center px-2 py-1 tw-m-1 text-sm rounded bg-gray-100 dark:bg-gray-800"
            >
              <span class="text-gray-900 dark:text-white">{{ selected.label }}</span>
              <button @click.prevent="removeItem(selected)"
                      class="tw-ml-1 tw-text-gray-500 tw-hover:text-gray-700 tw-dark:text-gray-400 tw-dark:hover:text-gray-200"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <input type="text"
                   v-model="search"
                   @focus="showDropdown = true"
                   :placeholder="field.placeholder || 'Search...'"
                   class="tw-w-full flex-grow min-w-[150px] p-1 outline-none border-none bg-transparent text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            />
          </div>

          <div v-if="showDropdown"
               class="tw-absolute tw-top-full dropDownMenu tw-z-50 w-full mt-1 rounded-lg shadow-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700"
          >
            <!-- Select All Option -->
            <div v-if="hasSelectableOptions"
                 @click="selectAllFiltered"
                 class="p-2 cursor-pointer text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 font-medium"
            >
              Select All Filtered ({{ filteredOptions.length }})
            </div>

            <div v-for="option in filteredOptions"
                 :key="option.value"
                 @click="selectItem(option)"
                 class="p-2 cursor-pointer text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              {{ option.label }}
            </div>

            <div v-if="!filteredOptions.length && !loading"
                 class="p-2 text-gray-500 dark:text-gray-400"
            >
              No options available
            </div>

            <div v-if="loading"
                 class="p-2 text-gray-500 dark:text-gray-400"
            >
              Loading...
            </div>
          </div>
        </div>
      </div>
    </template>
  </DefaultField>
</template>

<style scoped>
.hasManySearchable :deep(.dropDownMenu) {
  max-height: 300px;
  overflow-y: auto;
  scrollbar-width: thin;
}

.hasManySearchable :deep(.dropDownMenu::-webkit-scrollbar) {
  width: 6px;
}

.hasManySearchable :deep(.dropDownMenu::-webkit-scrollbar-track) {
  background: transparent;
}

.hasManySearchable :deep(.dropDownMenu::-webkit-scrollbar-thumb) {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

:deep(.multiselect-search input::placeholder) {
  @apply text-gray-400 dark:text-gray-500;
}
</style>