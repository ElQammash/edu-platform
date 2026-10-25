import { GraduationCap } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, Outlet } from 'react-router'
import { LanguageSwitcher } from '@/components/LanguageSwitcher'
import { cn } from '@/lib/utils'
import { paths } from '@/routes/paths'

const currentYear = new Date().getFullYear()

export default function MainLayout() {
  const { t } = useTranslation()

  const navItems = [
    { to: paths.catalog, label: t('pages.catalog.title') },
    { to: paths.myCourses, label: t('pages.myCourses.title') },
    { to: paths.myGrades, label: t('pages.myGrades.title') },
  ]

  return (
    <div className="flex min-h-svh flex-col">
      <header className="border-b">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
          <Link to={paths.home} className="flex items-center gap-2 font-bold">
            <GraduationCap className="size-6 text-primary" />
            {t('app.name')}
          </Link>

          <nav className="flex flex-wrap items-center gap-4 text-sm">
            {navItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) =>
                  cn('text-muted-foreground transition-colors hover:text-foreground', isActive && 'font-medium text-foreground')
                }
              >
                {item.label}
              </NavLink>
            ))}
          </nav>

          <div className="ms-auto flex items-center gap-2">
            <LanguageSwitcher />
          </div>
        </div>
      </header>

      <main className="flex-1">
        <Outlet />
      </main>

      <footer className="border-t py-6 text-center text-sm text-muted-foreground">
        © {currentYear} {t('app.name')}
      </footer>
    </div>
  )
}
