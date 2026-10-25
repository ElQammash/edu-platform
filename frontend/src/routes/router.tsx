import { createBrowserRouter } from 'react-router'
import AssignmentPage from '@/pages/AssignmentPage'
import CatalogPage from '@/pages/CatalogPage'
import CourseDetailsPage from '@/pages/CourseDetailsPage'
import HomePage from '@/pages/HomePage'
import LessonPage from '@/pages/LessonPage'
import LoginPage from '@/pages/LoginPage'
import MyCoursesPage from '@/pages/MyCoursesPage'
import MyGradesPage from '@/pages/MyGradesPage'
import NotFoundPage from '@/pages/NotFoundPage'
import ProfilePage from '@/pages/ProfilePage'
import QuizPage from '@/pages/QuizPage'
import RegisterPage from '@/pages/RegisterPage'
import { paths } from './paths'

export const router = createBrowserRouter([
  { path: paths.home, element: <HomePage /> },
  { path: paths.catalog, element: <CatalogPage /> },
  { path: paths.courseDetails(), element: <CourseDetailsPage /> },
  { path: paths.myCourses, element: <MyCoursesPage /> },
  { path: paths.lesson(), element: <LessonPage /> },
  { path: paths.assignment(), element: <AssignmentPage /> },
  { path: paths.quiz(), element: <QuizPage /> },
  { path: paths.myGrades, element: <MyGradesPage /> },
  { path: paths.profile, element: <ProfilePage /> },
  { path: paths.login, element: <LoginPage /> },
  { path: paths.register, element: <RegisterPage /> },
  { path: '*', element: <NotFoundPage /> },
])
