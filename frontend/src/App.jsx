import { Navigate, Route, Routes } from 'react-router'
import AdminLayout from './components/AdminLayout.jsx'
import Layout from './components/Layout.jsx'
import AdminCourtsPage from './pages/admin/AdminCourtsPage.jsx'
import AdminTournamentFormPage from './pages/admin/AdminTournamentFormPage.jsx'
import AdminTournamentPage from './pages/admin/AdminTournamentPage.jsx'
import AdminTournamentsPage from './pages/admin/AdminTournamentsPage.jsx'
import AdminUsersPage from './pages/admin/AdminUsersPage.jsx'
import LoginPage from './pages/LoginPage.jsx'
import NotFoundPage from './pages/NotFoundPage.jsx'
import RegisterPage from './pages/RegisterPage.jsx'
import TournamentListPage from './pages/TournamentListPage.jsx'
import TournamentPage from './pages/TournamentPage.jsx'

export default function App() {
  return (
    <Routes>
      <Route element={<Layout />}>
        <Route index element={<TournamentListPage />} />
        <Route path="tournaments/:id" element={<TournamentPage />} />
        <Route path="login" element={<LoginPage />} />
        <Route path="register" element={<RegisterPage />} />
        <Route path="admin" element={<AdminLayout />}>
          <Route index element={<Navigate to="tournaments" replace />} />
          <Route path="tournaments" element={<AdminTournamentsPage />} />
          <Route path="tournaments/new" element={<AdminTournamentFormPage />} />
          <Route path="tournaments/:id" element={<AdminTournamentPage />} />
          <Route path="tournaments/:id/edit" element={<AdminTournamentFormPage />} />
          <Route path="courts" element={<AdminCourtsPage />} />
          <Route path="users" element={<AdminUsersPage />} />
        </Route>
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  )
}
