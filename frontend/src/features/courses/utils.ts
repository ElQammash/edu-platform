import type { LessonSummary, Section } from '@/types/course'

/** The lesson a student should open next: the first one not completed yet. */
export function nextLessonToOpen(sections: Section[] | undefined): LessonSummary | undefined {
  const lessons = sections?.flatMap((section) => section.lessons) ?? []
  return lessons.find((lesson) => !lesson.is_completed) ?? lessons[0]
}
