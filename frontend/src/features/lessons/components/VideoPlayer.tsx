import VimeoPlayer from '@vimeo/player'
import playerjs from 'player.js'
import { useEffect, useRef } from 'react'
import type { Lesson } from '@/types/course'

type Video = NonNullable<Lesson['video']>

export interface PlaybackEvents {
  /** Called as the video plays, with the current position and total length (seconds). */
  onTime: (seconds: number, duration: number) => void
  /** Called on pause and at the end, a good moment to save progress. */
  onPause: () => void
}

/**
 * Embedded lesson video (YouTube, Vimeo or Bunny Stream) that reports the
 * playback position through each provider's player API. The video itself
 * stays on the provider; nothing is downloaded to our server.
 */
export function VideoPlayer({ video, title, events }: { video: Video; title: string; events: PlaybackEvents }) {
  const iframeRef = useRef<HTMLIFrameElement>(null)
  // Keep the latest callbacks without re-creating the player on every render.
  const eventsRef = useRef(events)
  useEffect(() => {
    eventsRef.current = events
  })

  useEffect(() => {
    const iframe = iframeRef.current
    if (!iframe) return

    const onTime = (seconds: number, duration: number) => eventsRef.current.onTime(seconds, duration)
    const onPause = () => eventsRef.current.onPause()

    switch (video.provider) {
      case 'youtube':
        return trackYouTube(iframe, onTime, onPause)
      case 'vimeo':
        return trackVimeo(iframe, onTime, onPause)
      case 'bunny':
        return trackBunny(iframe, onTime, onPause)
    }
  }, [video.provider, video.embed_url])

  return (
    <div className="aspect-video overflow-hidden rounded-lg bg-black">
      <iframe
        ref={iframeRef}
        src={embedSrc(video)}
        title={title}
        className="size-full"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
        allowFullScreen
        referrerPolicy="strict-origin-when-cross-origin"
      />
    </div>
  )
}

function embedSrc(video: Video): string {
  if (video.provider !== 'youtube') return video.embed_url

  // enablejsapi lets the IFrame API attach to this iframe.
  const params = new URLSearchParams({ enablejsapi: '1', origin: window.location.origin, rel: '0' })
  return `${video.embed_url}?${params}`
}

type Unsubscribe = () => void

let youTubeApi: Promise<typeof YT> | null = null

function loadYouTubeApi(): Promise<typeof YT> {
  youTubeApi ??= new Promise((resolve) => {
    if (window.YT?.Player) return resolve(window.YT)
    window.onYouTubeIframeAPIReady = () => resolve(window.YT!)
    const script = document.createElement('script')
    script.src = 'https://www.youtube.com/iframe_api'
    document.head.appendChild(script)
  })
  return youTubeApi
}

function trackYouTube(iframe: HTMLIFrameElement, onTime: PlaybackEvents['onTime'], onPause: PlaybackEvents['onPause']): Unsubscribe {
  let player: YT.Player | null = null
  let timer: ReturnType<typeof setInterval> | undefined
  let cancelled = false

  // The IFrame API has no time event, so poll while the video plays.
  const poll = () => {
    if (player) onTime(player.getCurrentTime(), player.getDuration())
  }

  void loadYouTubeApi().then((api) => {
    if (cancelled) return
    player = new api.Player(iframe, {
      events: {
        onStateChange: ({ data }) => {
          clearInterval(timer)
          if (data === 1) {
            timer = setInterval(poll, 5000)
          } else if (data === 2 || data === 0) {
            poll()
            onPause()
          }
        },
      },
    })
  })

  return () => {
    cancelled = true
    clearInterval(timer)
  }
}

function trackVimeo(iframe: HTMLIFrameElement, onTime: PlaybackEvents['onTime'], onPause: PlaybackEvents['onPause']): Unsubscribe {
  const player = new VimeoPlayer(iframe)
  player.on('timeupdate', ({ seconds, duration }) => onTime(seconds, duration))
  player.on('pause', onPause)
  player.on('ended', onPause)

  return () => {
    player.off('timeupdate')
    player.off('pause')
    player.off('ended')
  }
}

function trackBunny(iframe: HTMLIFrameElement, onTime: PlaybackEvents['onTime'], onPause: PlaybackEvents['onPause']): Unsubscribe {
  const player = new playerjs.Player(iframe)
  player.on('ready', () => {
    player.on('timeupdate', ({ seconds, duration }) => onTime(seconds, duration))
    player.on('pause', onPause)
    player.on('ended', onPause)
  })

  return () => {
    player.off('timeupdate')
    player.off('pause')
    player.off('ended')
  }
}
