import { CircleUser, LayoutDashboard, LogOut, Receipt } from 'lucide-react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useAuth, useLogout } from '@/features/auth/hooks'
import { panelUrlFor } from '@/features/auth/redirects'
import { paths } from '@/routes/paths'

/** Header area: login/register links for guests, account menu for users. */
export function UserMenu() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const { user, isLoading } = useAuth()
  const logout = useLogout()

  if (isLoading) return null

  if (!user) {
    return (
      <>
        <Button asChild variant="ghost" size="sm">
          <Link to={paths.login}>{t('pages.login.title')}</Link>
        </Button>
        <Button asChild size="sm">
          <Link to={paths.register}>{t('auth.register.submit')}</Link>
        </Button>
      </>
    )
  }

  const panelUrl = panelUrlFor(user)

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm">
          <CircleUser />
          {user.name}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="min-w-48">
        <DropdownMenuLabel className="font-normal">
          <div className="font-medium">{user.name}</div>
          <div className="text-xs text-muted-foreground" dir="ltr">
            {user.email}
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        {panelUrl ? (
          <DropdownMenuItem asChild>
            <a href={panelUrl}>
              <LayoutDashboard />
              {t('auth.menu.dashboard')}
            </a>
          </DropdownMenuItem>
        ) : (
          <>
            <DropdownMenuItem asChild>
              <Link to={paths.profile}>
                <CircleUser />
                {t('pages.profile.title')}
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to={paths.myPayments}>
                <Receipt />
                {t('pages.myPayments.title')}
              </Link>
            </DropdownMenuItem>
          </>
        )}
        <DropdownMenuItem
          disabled={logout.isPending}
          onSelect={() => logout.mutate(undefined, { onSuccess: () => navigate(paths.home, { replace: true }) })}
        >
          <LogOut />
          {t('auth.menu.logout')}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
