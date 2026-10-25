import { RouterProvider } from 'react-router'
import { AppProviders } from '@/components/AppProviders'
import { router } from '@/routes/router'

function App() {
  return (
    <AppProviders>
      <RouterProvider router={router} />
    </AppProviders>
  )
}

export default App
