import { useEffect, useState } from 'react'

/**
 * Milliseconds left until `expiresAt`, by the server's clock: the offset
 * between the server time sent with the attempt and the device clock is
 * applied, so a wrong device clock does not shorten or extend the quiz.
 * Returns null for untimed attempts (and for the first instant, before the
 * clock is read).
 */
export function useCountdown(expiresAt: string | null, serverTime: string): number | null {
  const [left, setLeft] = useState<number | null>(null)

  useEffect(() => {
    if (!expiresAt) return

    // Measured when the attempt was loaded (serverTime changes on reload).
    const offset = Date.parse(serverTime) - Date.now()
    const deadline = Date.parse(expiresAt)
    const tick = () => setLeft(Math.max(0, deadline - (Date.now() + offset)))

    tick()
    const timer = setInterval(tick, 1000)
    return () => clearInterval(timer)
  }, [expiresAt, serverTime])

  return expiresAt ? left : null
}

/** 125000 -> "2:05"; over an hour -> "1:02:05". */
export function formatClock(ms: number): string {
  const total = Math.ceil(ms / 1000)
  const h = Math.floor(total / 3600)
  const m = Math.floor((total % 3600) / 60)
  const s = total % 60
  const pad = (n: number) => String(n).padStart(2, '0')

  return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`
}
