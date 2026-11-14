import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router'
import { apiFetch } from '../../api.js'
import { errorMessage, fieldErrors } from '../../labels.js'

const emptyForm = { name: '', format: 'AMERICANO', startsAt: '', pointsPerMatch: 24, maxPlayers: '', courtIds: [] }

function toInputDate(value) {
  const date = new Date(value)
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

export default function AdminTournamentFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [form, setForm] = useState(emptyForm)
  const [courts, setCourts] = useState(null)
  const [errors, setErrors] = useState({})
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    apiFetch('/admin/courts').then(setCourts).catch((e) => setError(errorMessage(e)))

    if (id) {
      apiFetch(`/admin/tournaments/${id}`)
        .then((tournament) => setForm({
          name: tournament.name,
          format: tournament.format,
          startsAt: toInputDate(tournament.startsAt),
          pointsPerMatch: tournament.pointsPerMatch,
          maxPlayers: tournament.maxPlayers,
          courtIds: tournament.courts.map((court) => court.id),
        }))
        .catch((e) => setError(errorMessage(e)))
    }
  }, [id])

  function handleChange(event) {
    setForm({ ...form, [event.target.name]: event.target.value })
  }

  function toggleCourt(courtId) {
    if (form.courtIds.includes(courtId)) {
      setForm({ ...form, courtIds: form.courtIds.filter((item) => item !== courtId) })
    } else {
      setForm({ ...form, courtIds: [...form.courtIds, courtId] })
    }
  }

  async function handleSubmit(event) {
    event.preventDefault()
    setErrors({})
    setError('')

    if (form.courtIds.length === 0) {
      setErrors({ courtIds: 'Wybierz co najmniej jeden kort.' })
      return
    }

    setSubmitting(true)
    const body = {
      ...form,
      startsAt: new Date(form.startsAt).toISOString(),
      pointsPerMatch: Number(form.pointsPerMatch),
      maxPlayers: Number(form.maxPlayers),
    }

    try {
      const tournament = id
        ? await apiFetch(`/admin/tournaments/${id}`, { method: 'PUT', body })
        : await apiFetch('/admin/tournaments', { method: 'POST', body })
      navigate(`/admin/tournaments/${tournament.id}`)
    } catch (e) {
      if (e.code === 'VALIDATION_ERROR') {
        setErrors(fieldErrors(e))
      } else {
        setError(errorMessage(e))
      }
      setSubmitting(false)
    }
  }

  if (courts === null) {
    return error ? <p className="error">{error}</p> : <p>Ładowanie…</p>
  }

  const visibleCourts = courts.filter((court) => court.active || form.courtIds.includes(court.id))

  return (
    <div className="card form-card wide">
      <h1>{id ? 'Edycja turnieju' : 'Nowy turniej'}</h1>
      <form onSubmit={handleSubmit}>
        <label>
          Nazwa
          <input name="name" value={form.name} onChange={handleChange} required maxLength={150} />
          {errors.name && <span className="error">{errors.name}</span>}
        </label>
        <label>
          Format
          <select name="format" value={form.format} onChange={handleChange}>
            <option value="AMERICANO">Americano</option>
            <option value="MEXICANO">Mexicano</option>
          </select>
        </label>
        <label>
          Data i godzina startu
          <input type="datetime-local" name="startsAt" value={form.startsAt} onChange={handleChange} required />
          {errors.startsAt && <span className="error">{errors.startsAt}</span>}
        </label>
        <label>
          Pula punktów w meczu
          <input type="number" name="pointsPerMatch" value={form.pointsPerMatch} onChange={handleChange} required min={1} max={100} />
          {errors.pointsPerMatch && <span className="error">{errors.pointsPerMatch}</span>}
        </label>
        <fieldset>
          <legend>Korty</legend>
          {visibleCourts.map((court) => (
            <label key={court.id} className="checkbox">
              <input type="checkbox" checked={form.courtIds.includes(court.id)} onChange={() => toggleCourt(court.id)} />
              {court.name} {!court.active && '(nieaktywny)'}
            </label>
          ))}
          {errors.courtIds && <span className="error">{errors.courtIds}</span>}
        </fieldset>
        <label>
          Maks. liczba graczy (najwyżej {4 * form.courtIds.length})
          <input type="number" name="maxPlayers" value={form.maxPlayers} onChange={handleChange} required min={4} max={Math.max(4, 4 * form.courtIds.length)} />
          {errors.maxPlayers && <span className="error">{errors.maxPlayers}</span>}
        </label>
        {error && <p className="error">{error}</p>}
        <div className="actions">
          <button type="submit" disabled={submitting}>Zapisz</button>
          <Link to={id ? `/admin/tournaments/${id}` : '/admin/tournaments'}>Anuluj</Link>
        </div>
      </form>
    </div>
  )
}
