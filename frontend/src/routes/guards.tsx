import { useEffect } from 'react'
import { Navigate, Outlet, useLocation } from 'react-router'
import { FullPageSpinner } from '@/components/FullPageSpinner'
import { useAuth } from '@/features/auth/hooks'
import { panelUrlFor } from '@/features/auth/redirects'
import type { Role } from '@/types/user'
import { paths } from './paths'

/**
 * Only signed-in users (optionally with a given role) may see these routes.
 * Guests go to the login page and come back afterwards.
 */
export function RequireAuth({ role }: { role?: Role }) {
  const { isLoading, isAuthenticated, hasRole } = useAuth()
  const location = useLocation()

  if (isLoading) return <FullPageSpinner />

  if (!isAuthenticated) {
    return <Navigate to={paths.login} replace state={{ from: location.pathname + location.search }} />
  }

  if (role && !hasRole(role)) return <Navigate to={paths.home} replace />

  return <Outlet />
}

/**
 * Login and register pages, for guests only. This is also where users go
 * right after signing in: as soon as the user lands in the auth cache, this
 * guard sends them back to the page that required login (or home), and
 * admins/teachers to their Filament panel.
 */
export function GuestOnly() {
  const { user, isLoading } = useAuth()
  const location = useLocation()
  const panelUrl = user ? panelUrlFor(user) : null

  useEffect(() => {
    if (panelUrl) window.location.assign(panelUrl)
  }, [panelUrl])

  if (isLoading || panelUrl) return <FullPageSpinner />

  if (user) {
    const from = (location.state as { from?: string } | null)?.from ?? paths.home
    return <Navigate to={from} replace />
  }

  return <Outlet />
}
