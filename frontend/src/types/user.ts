export type Role = 'admin' | 'teacher' | 'student'

export interface StudentProfile {
  grade_level: string | null
  parent_phone: string | null
  avatar_url: string | null
}

export interface TeacherProfile {
  bio: string | null
  specialty: string | null
  avatar_url: string | null
  rating: number
  is_approved: boolean
}

export interface User {
  id: number
  name: string
  email: string
  roles: Role[]
  /** Saved interface language; null until chosen. */
  locale: 'ar' | 'en' | null
  student_profile?: StudentProfile | null
  teacher_profile?: TeacherProfile | null
  created_at: string | null
}
