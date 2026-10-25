import { useCallback, useEffect, useRef } from 'react'
import type { PlaybackEvents } from './components/VideoPlayer'

/** Send progress at most this often while the video plays (seconds of video). */
const REPORT_EVERY_SECONDS = 15

/**
 * Turns the player's frequent time updates into occasional progress
 * reports: every 15 s of newly reached video, on pause/end, when the tab
 * is hidden and when leaving the lesson. Only the furthest position is
 * sent (the server keeps the maximum anyway).
 */
export function useWatchProgress(
  enabled: boolean,
  report: (watched: number, duration: number) => void,
): PlaybackEvents {
  const furthest = useRef(0)
  const duration = useRef(0)
  const lastSent = useRef(0)
  const reportRef = useRef(report)
  useEffect(() => {
    reportRef.current = report
  })

  const flush = useCallback(() => {
    if (!enabled || furthest.current <= lastSent.current) return
    lastSent.current = furthest.current
    reportRef.current(furthest.current, duration.current)
  }, [enabled])

  useEffect(() => {
    const onHidden = () => document.visibilityState === 'hidden' && flush()
    document.addEventListener('visibilitychange', onHidden)
    return () => {
      document.removeEventListener('visibilitychange', onHidden)
      flush()
    }
  }, [flush])

  return {
    onTime: (seconds, total) => {
      if (total > 0) duration.current = total
      if (seconds > furthest.current) furthest.current = seconds
      if (furthest.current - lastSent.current >= REPORT_EVERY_SECONDS) flush()
    },
    onPause: flush,
  }
}
