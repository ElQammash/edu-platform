import { useTranslation } from 'react-i18next'
import { DirectionProvider } from '@/components/ui/direction'
import { useLanguage } from '@/hooks/useLanguage'

function App() {
  const { t } = useTranslation()
  const { dir } = useLanguage()

  return (
    <DirectionProvider dir={dir}>
      <main className="flex min-h-svh flex-col items-center justify-center gap-2 text-center">
        <h1 className="text-3xl font-bold">{t('app.name')}</h1>
        <p className="text-muted-foreground">{t('app.tagline')}</p>
      </main>
    </DirectionProvider>
  )
}

export default App
