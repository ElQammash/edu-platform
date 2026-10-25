import { Languages } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useLanguage } from '@/hooks/useLanguage'
import { SUPPORTED_LANGUAGES, type Language } from '@/lib/i18n'

export function LanguageSwitcher() {
  const { t } = useTranslation()
  const { language, setLanguage } = useLanguage()

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="sm" aria-label={t('language.label')}>
          <Languages />
          {t(`language.${language}`)}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>{t('language.label')}</DropdownMenuLabel>
        <DropdownMenuRadioGroup value={language} onValueChange={(value) => setLanguage(value as Language)}>
          {SUPPORTED_LANGUAGES.map((lng) => (
            <DropdownMenuRadioItem key={lng} value={lng} lang={lng}>
              {t(`language.${lng}`)}
            </DropdownMenuRadioItem>
          ))}
        </DropdownMenuRadioGroup>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
