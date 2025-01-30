import IndexField from './components/IndexField'
import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('index-belongs-to-many', IndexField)
  app.component('detail-belongs-to-many', DetailField)
  app.component('form-belongs-to-many', FormField)
})