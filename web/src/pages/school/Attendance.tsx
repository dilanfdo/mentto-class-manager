import { useEffect, useState } from 'react'
import { Button, Card, ErrorBox, Input, Loading, PageHeader, Select } from '../../components/ui'
import { api } from '../../lib/api'
import { today } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClassGroup } from './types'

type Sheet = { date: string; marked: boolean; students: { id: number; name: string; present: boolean | null }[] }

export default function Attendance() {
  const classes = useApi<{ data: ClassGroup[] }>('/classes')
  const [chosenClass, setClassId] = useState('')
  const classId = chosenClass || String(classes.data?.data[0]?.id ?? '')
  const [date, setDate] = useState(today())
  const sheet = useApi<Sheet>(classId ? `/classes/${classId}/attendance?date=${date}` : null)
  const [marks, setMarks] = useState<Record<number, boolean>>({})
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (sheet.data) setMarks(Object.fromEntries(sheet.data.students.map((s) => [s.id, s.present ?? true])))
    setSaved(false)
  }, [sheet.data])

  async function save() {
    setError(null)
    try {
      await api(`/classes/${classId}/attendance`, { method: 'PUT', body: { date, records: Object.entries(marks).map(([id, present]) => ({ student_id: Number(id), present })) } })
      setSaved(true)
    } catch (e) {
      setError((e as Error).message)
    }
  }

  const present = Object.values(marks).filter(Boolean).length

  return (
    <>
      <PageHeader title="Attendance" subtitle="Everyone starts as present. Tap anyone who was absent, then save." />
      <div className="mb-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_200px]">
        <Select aria-label="Class" value={classId} onChange={(e) => setClassId(e.target.value)}>
          {classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
        </Select>
        <Input type="date" aria-label="Date" value={date} onChange={(e) => setDate(e.target.value)} />
      </div>
      {(sheet.error || error) && <ErrorBox message={(sheet.error || error)!} />}
      {sheet.loading && !sheet.data ? <Loading /> : sheet.data && (
        <Card className="p-4">
          <p className="mb-3 text-sm text-slate-600">{present} of {sheet.data.students.length} present{sheet.data.marked ? ' · already marked, changes update it' : ''}</p>
          <ul className="grid gap-2 sm:grid-cols-2">
            {sheet.data.students.map((s) => (
              <li key={s.id}>
                <button
                  type="button"
                  aria-pressed={marks[s.id]}
                  onClick={() => { setMarks({ ...marks, [s.id]: !marks[s.id] }); setSaved(false) }}
                  className={`flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm ${marks[s.id] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50'}`}
                >
                  {s.name}
                  <span className={`text-xs font-semibold ${marks[s.id] ? 'text-emerald-700' : 'text-rose-700'}`}>{marks[s.id] ? 'Present' : 'Absent'}</span>
                </button>
              </li>
            ))}
          </ul>
          {sheet.data.students.length === 0 ? <p className="text-sm text-slate-500">No students in this class yet.</p> : (
            <div className="mt-4 flex items-center gap-3">
              <Button onClick={() => void save()}>Save attendance</Button>
              {saved && <span role="status" className="text-sm text-emerald-700">Saved.</span>}
            </div>
          )}
        </Card>
      )}
    </>
  )
}
