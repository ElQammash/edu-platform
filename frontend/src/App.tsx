import { useTranslation } from 'react-i18next'
import { AppProviders } from '@/components/AppProviders'

function Home() {
  const { t } = useTranslation()

  return (
    <main className="flex min-h-svh flex-col items-center justify-center gap-2 text-center">
      <h1 className="text-3xl font-bold">{t('app.name')}</h1>
      <p className="text-muted-foreground">{t('app.tagline')}</p>
    </main>
  )
}

function App() {
  return (
    <AppProviders>
      <Home />
    </AppProviders>
  )
}

export default App
