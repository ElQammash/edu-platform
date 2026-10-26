import { createBrowserRouter } from 'react-router'
import MainLayout from '@/layouts/MainLayout'
import AssignmentPage from '@/pages/AssignmentPage'
import AttemptPage from '@/pages/AttemptPage'
import CatalogPage from '@/pages/CatalogPage'
import CourseDetailsPage from '@/pages/CourseDetailsPage'
import HomePage from '@/pages/HomePage'
import LessonPage from '@/pages/LessonPage'
import LoginPage from '@/pages/LoginPage'
import MyCoursesPage from '@/pages/MyCoursesPage'
import MyGradesPage from '@/pages/MyGradesPage'
import MyPaymentsPage from '@/pages/MyPaymentsPage'
import NotFoundPage from '@/pages/NotFoundPage'
import PaymentResultPage from '@/pages/PaymentResultPage'
import ProfilePage from '@/pages/ProfilePage'
import QuizPage from '@/pages/QuizPage'
import RegisterPage from '@/pages/RegisterPage'
import TeacherPage from '@/pages/TeacherPage'
import { GuestOnly, RequireAuth } from './guards'
import { paths } from './paths'

export const router = createBrowserRouter([
  {
    element: <MainLayout />,
    children: [
      // Public
      { path: paths.home, element: <HomePage /> },
      { path: paths.catalog, element: <CatalogPage /> },
      { path: paths.courseDetails(), element: <CourseDetailsPage /> },
      // Free previews are open to guests; the API decides access to the rest.
      { path: paths.lesson(), element: <LessonPage /> },
      { path: paths.teacher(), element: <TeacherPage /> },

      // Guests only
      {
        element: <GuestOnly />,
        children: [
          { path: paths.login, element: <LoginPage /> },
          { path: paths.register, element: <RegisterPage /> },
        ],
      },

      // Signed-in students
      {
        element: <RequireAuth role="student" />,
        children: [
          { path: paths.myCourses, element: <MyCoursesPage /> },
          { path: paths.assignment(), element: <AssignmentPage /> },
          { path: paths.quiz(), element: <QuizPage /> },
          { path: paths.attempt(), element: <AttemptPage /> },
          { path: paths.myGrades, element: <MyGradesPage /> },
          { path: paths.myPayments, element: <MyPaymentsPage /> },
          { path: paths.payment(), element: <PaymentResultPage /> },
          { path: paths.profile, element: <ProfilePage /> },
        ],
      },

      { path: '*', element: <NotFoundPage /> },
    ],
  },
])
