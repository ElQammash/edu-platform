import ReactMarkdown, { type Components } from 'react-markdown'
import { cn } from '@/lib/utils'

/**
 * Renders teacher-written Markdown. react-markdown does not render raw
 * HTML, so descriptions and lesson notes cannot inject scripts. Each
 * block uses dir="auto" so Arabic and English paragraphs read correctly
 * in either page direction.
 */
const components: Components = {
  h1: ({ node: _node, ...props }) => <h2 dir="auto" className="mt-6 mb-3 text-2xl font-bold" {...props} />,
  h2: ({ node: _node, ...props }) => <h2 dir="auto" className="mt-6 mb-3 text-xl font-bold" {...props} />,
  h3: ({ node: _node, ...props }) => <h3 dir="auto" className="mt-5 mb-2 text-lg font-semibold" {...props} />,
  p: ({ node: _node, ...props }) => <p dir="auto" className="my-3 leading-7" {...props} />,
  ul: ({ node: _node, ...props }) => <ul dir="auto" className="my-3 list-disc space-y-1 ps-6" {...props} />,
  ol: ({ node: _node, ...props }) => <ol dir="auto" className="my-3 list-decimal space-y-1 ps-6" {...props} />,
  blockquote: ({ node: _node, ...props }) => (
    <blockquote dir="auto" className="my-4 border-s-4 ps-4 text-muted-foreground italic" {...props} />
  ),
  a: ({ node: _node, ...props }) => (
    <a className="font-medium text-primary underline underline-offset-4" target="_blank" rel="noopener noreferrer" {...props} />
  ),
  code: ({ node: _node, ...props }) => <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-sm" dir="ltr" {...props} />,
  hr: () => <hr className="my-6" />,
}

export function Markdown({ children, className }: { children: string | null | undefined; className?: string }) {
  if (!children) return null

  return (
    <div className={cn('text-foreground', className)}>
      <ReactMarkdown components={components}>{children}</ReactMarkdown>
    </div>
  )
}
