/** Single source of truth for URLs, so links and routes never drift apart. */
export const paths = {
  home: '/',
  catalog: '/courses',
  courseDetails: (slug = ':slug') => `/courses/${slug}`,
  myCourses: '/my-courses',
  lesson: (lessonId: string | number = ':lessonId') => `/lessons/${lessonId}`,
  assignment: (assignmentId: string | number = ':assignmentId') => `/assignments/${assignmentId}`,
  quiz: (quizId: string | number = ':quizId') => `/quizzes/${quizId}`,
  attempt: (attemptId: string | number = ':attemptId') => `/attempts/${attemptId}`,
  teacher: (teacherId: string | number = ':teacherId') => `/teachers/${teacherId}`,
  myGrades: '/my-grades',
  myPayments: '/my-payments',
  payment: (reference = ':reference') => `/payments/${reference}`,
  profile: '/profile',
  login: '/login',
  register: '/register',
} as const
