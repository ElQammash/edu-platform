/** Single source of truth for URLs, so links and routes never drift apart. */
export const paths = {
  home: '/',
  catalog: '/courses',
  courseDetails: (slug = ':slug') => `/courses/${slug}`,
  myCourses: '/my-courses',
  lesson: (lessonId: string | number = ':lessonId') => `/lessons/${lessonId}`,
  assignment: (assignmentId: string | number = ':assignmentId') => `/assignments/${assignmentId}`,
  quiz: (quizId: string | number = ':quizId') => `/quizzes/${quizId}`,
  teacher: (teacherId: string | number = ':teacherId') => `/teachers/${teacherId}`,
  myGrades: '/my-grades',
  profile: '/profile',
  login: '/login',
  register: '/register',
} as const
