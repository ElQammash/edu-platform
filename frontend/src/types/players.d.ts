/** Minimal typings for the YouTube IFrame API (loaded at runtime). */
declare namespace YT {
  enum PlayerState {
    UNSTARTED = -1,
    ENDED = 0,
    PLAYING = 1,
    PAUSED = 2,
    BUFFERING = 3,
    CUED = 5,
  }

  interface OnStateChangeEvent {
    data: PlayerState
    target: Player
  }

  interface PlayerOptions {
    events?: {
      onReady?: (event: { target: Player }) => void
      onStateChange?: (event: OnStateChangeEvent) => void
    }
  }

  class Player {
    constructor(element: HTMLIFrameElement | string, options?: PlayerOptions)
    getCurrentTime(): number
    getDuration(): number
    getPlayerState(): PlayerState
    destroy(): void
  }
}

interface Window {
  YT?: typeof YT
  onYouTubeIframeAPIReady?: () => void
}

/** player.js protocol (used by Bunny Stream embeds). */
declare module 'player.js' {
  interface TimeUpdate {
    seconds: number
    duration: number
  }

  class Player {
    constructor(iframe: HTMLIFrameElement)
    on(event: 'ready' | 'play' | 'pause' | 'ended', callback: () => void): void
    on(event: 'timeupdate', callback: (data: TimeUpdate) => void): void
    off(event: string): void
  }

  const playerjs: { Player: typeof Player }
  export default playerjs
}
