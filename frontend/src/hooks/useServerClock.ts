import { useEffect, useState } from 'react'

/**
 * The server's current time in ms, ticking every second. `serverTime` is the
 * server's clock when the data was loaded; the device clock only measures
 * time passing, so a wrong device clock does not shift deadlines. Returns
 * null for the first instant, before the clock is read.
 */
export function useServerClock(serverTime: string): number | null {
  const [now, setNow] = useState<number | null>(null)

  useEffect(() => {
    const offset = Date.parse(serverTime) - Date.now()
    const tick = () => setNow(Date.now() + offset)

    tick()
    const timer = setInterval(tick, 1000)
    return () => clearInterval(timer)
  }, [serverTime])

  return now
}
