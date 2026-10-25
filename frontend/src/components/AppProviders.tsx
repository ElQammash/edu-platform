import type { ReactNode } from 'react'
import { QueryClientProvider } from '@tanstack/react-query'
import { DirectionProvider } from '@/components/ui/direction'
import { useLanguage } from '@/hooks/useLanguage'
import { queryClient } from '@/lib/queryClient'

export function AppProviders({ children }: { children: ReactNode }) {
  const { dir } = useLanguage()

  return (
    <QueryClientProvider client={queryClient}>
      <DirectionProvider dir={dir}>{children}</DirectionProvider>
    </QueryClientProvider>
  )
}
