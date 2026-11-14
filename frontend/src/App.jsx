import { Route, Routes } from 'react-router'
import Layout from './components/Layout.jsx'
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
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  )
}
