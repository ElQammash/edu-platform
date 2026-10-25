import { LoaderCircle } from 'lucide-react'

export function FullPageSpinner() {
  return (
    <div className="flex min-h-[50svh] items-center justify-center" role="status">
      <LoaderCircle className="size-8 animate-spin text-muted-foreground" />
    </div>
  )
}
