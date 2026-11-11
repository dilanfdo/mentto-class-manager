import { useState, type FormEvent } from 'react'
import { Button, Card, ErrorBox, Field, Input, Loading, Modal, PageHeader, Select, Table } from '../../components/ui'
import { api, ApiError } from '../../lib/api'
import { today } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClassGroup } from './types'

type Exam = { id: number; name: string; held_on: string; max_marks: number; marks_count: number; class_group: { id: number; name: string } }
type Results = Exam & { results: { student_id: number; name: string; marks: number; rank: number }[] }

export default function Exams() {
  const exams = useApi<{ data: Exam[] }>('/exams')
  const classes = useApi<{ data: ClassGroup[] }>('/classes')
  const [openId, setOpenId] = useState<number | null>(null)
  const results = useApi<{ data: Results }>(openId ? `/exams/${openId}` : null)
  const [adding, setAdding] = useState(false)
  const [form, setForm] = useState({ class_group_id: '', name: '', held_on: today(), max_marks: '100' })
  const [marks, setMarks] = useState<Record<number, string>>({})
  const classSheet = useApi<{ data: { students: { id: number; name: string }[] } }>(form.class_group_id ? `/classes/${form.class_group_id}` : null)
  const [error, setError] = useState<string | null>(null)

  async function save(e: FormEvent) {
    e.preventDefault()
    setError(null)
    try {
      const res = await api<{ data: Results }>('/exams', {
        method: 'POST',
        body: {
          ...form,
          class_group_id: Number(form.class_group_id),
          max_marks: Number(form.max_marks),
          marks: Object.entries(marks).filter(([, m]) => m !== '').map(([id, m]) => ({ student_id: Number(id), marks: Number(m) })),
        },
      })
      setAdding(false)
      setMarks({})
      await exams.reload()
      setOpenId(res.data.id)
    } catch (err) {
      setError(err instanceof ApiError ? Object.values(err.fields)[0]?.[0] ?? err.message : (err as Error).message)
    }
  }

  return (
    <>
      <PageHeader title="Exams and results" subtitle="Enter marks once; ranks are worked out for you." actions={<Button onClick={() => setAdding(true)}>Add paper</Button>} />
      {exams.error && <ErrorBox message={exams.error} />}
      {exams.loading && !exams.data ? <Loading /> : (
        <Card>
          <Table head={['Paper', 'Class', 'Date', 'Marked']}>
            {exams.data?.data.map((x) => (
              <tr key={x.id}>
                <td className="px-4 py-3"><button type="button" className="font-medium hover:underline" onClick={() => setOpenId(x.id)}>{x.name}</button></td>
                <td className="px-4 py-3 text-slate-600">{x.class_group.name}</td>
                <td className="px-4 py-3 text-slate-600">{x.held_on}</td>
                <td className="px-4 py-3 tabular-nums">{x.marks_count}</td>
              </tr>
            ))}
            {exams.data?.data.length === 0 && <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-500">No papers yet.</td></tr>}
          </Table>
        </Card>
      )}

      {openId && results.data && (
        <Modal title={`${results.data.data.name} · results`} onClose={() => setOpenId(null)}>
          <Table head={['Rank', 'Student', `Marks / ${results.data.data.max_marks}`]}>
            {results.data.data.results.map((r) => (
              <tr key={r.student_id}><td className="px-4 py-2 tabular-nums">{r.rank}</td><td className="px-4 py-2">{r.name}</td><td className="px-4 py-2 tabular-nums">{r.marks}</td></tr>
            ))}
          </Table>
        </Modal>
      )}

      {adding && (
        <Modal title="Add a paper" onClose={() => setAdding(false)}>
          <form onSubmit={save} className="grid gap-3">
            {error && <ErrorBox message={error} />}
            <Field label="Class">
              <Select required value={form.class_group_id} onChange={(e) => { setForm({ ...form, class_group_id: e.target.value }); setMarks({}) }}>
                <option value="">Choose a class</option>
                {classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
            </Field>
            <div className="grid gap-3 sm:grid-cols-3">
              <Field label="Paper name"><Input required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></Field>
              <Field label="Date"><Input required type="date" value={form.held_on} onChange={(e) => setForm({ ...form, held_on: e.target.value })} /></Field>
              <Field label="Out of"><Input required type="number" min={1} value={form.max_marks} onChange={(e) => setForm({ ...form, max_marks: e.target.value })} /></Field>
            </div>
            {classSheet.data && (
              <div className="grid gap-2">
                <p className="text-sm font-medium text-slate-700">Marks</p>
                {classSheet.data.data.students.map((s) => (
                  <label key={s.id} className="grid grid-cols-[minmax(0,1fr)_100px] items-center gap-2 text-sm">
                    {s.name}
                    <Input type="number" min={0} max={Number(form.max_marks)} aria-label={`Marks for ${s.name}`} value={marks[s.id] ?? ''} onChange={(e) => setMarks({ ...marks, [s.id]: e.target.value })} />
                  </label>
                ))}
              </div>
            )}
            <div className="flex gap-2 pt-2"><Button type="submit">Save and rank</Button><Button variant="ghost" onClick={() => setAdding(false)}>Cancel</Button></div>
          </form>
        </Modal>
      )}
    </>
  )
}
