import type { AssignmentSummary } from './assignment'
import type { QuizSummary } from './quiz'

export type CourseLanguage = 'ar' | 'en'
export type CourseType = 'recorded' | 'live' | 'hybrid'
export type LessonType = 'video' | 'live' | 'text'
export type PaymentStatus = 'free' | 'manual'
export type VideoProvider = 'youtube' | 'vimeo' | 'bunny'

export interface TeacherSummary {
  id: number
  name: string
  specialty: string | null
  avatar_url: string | null
  rating: number
}

export interface Teacher extends TeacherSummary {
  bio: string | null
  courses_count: number
  courses: Course[]
}

export interface Enrollment {
  id: number
  payment_status: PaymentStatus
  enrolled_at: string
  progress_percent: number
  course?: Course
}

/** A curriculum entry: never carries the lesson's content or video. */
export interface LessonSummary {
  id: number
  title: string
  type: LessonType
  duration_seconds: number | null
  is_free_preview: boolean
  order: number
  /** Present when the signed-in student's progress was loaded. */
  is_completed?: boolean
}

export interface Section {
  id: number
  title: string
  order: number
  lessons: LessonSummary[]
}

export interface Course {
  id: number
  slug: string
  title: string
  excerpt: string
  /** Markdown; only on the course details endpoint. */
  description?: string | null
  thumbnail_url: string | null
  price: number
  is_free: boolean
  language: CourseLanguage
  type: CourseType
  published_at: string | null
  teacher?: TeacherSummary
  lessons_count?: number
  students_count?: number
  total_duration_seconds?: number
  sections?: Section[]
  /** The signed-in user's enrollment; null when not enrolled, absent for guests. */
  enrollment?: Enrollment | null
}

export interface LessonProgress {
  lesson_id: number
  watched_seconds: number
  is_completed: boolean
  completed_at: string | null
}

export interface Lesson {
  id: number
  title: string
  type: LessonType
  /** Markdown. */
  content: string | null
  video: { provider: VideoProvider; video_id: string; embed_url: string } | null
  duration_seconds: number | null
  is_free_preview: boolean
  section: { id: number; title: string }
  course: { id: number; slug: string; title: string }
  /** Present when the viewer can open it (not for free-preview visitors). */
  assignment?: AssignmentSummary | null
  quiz?: QuizSummary | null
  previous_lesson_id: number | null
  next_lesson_id: number | null
  /** Present for enrolled students; null before they start. */
  progress?: LessonProgress | null
}

export interface CourseFilters {
  search?: string
  language?: CourseLanguage
  type?: CourseType
  price?: 'free' | 'paid'
  teacher?: number
  page?: number
}
