import { useState, type FormEvent } from 'react'
import { Badge, Button, Card, ErrorBox, Field, Input, Loading, Modal, PageHeader, Select, Table } from '../../components/ui'
import { api, ApiError } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { useApi } from '../../lib/useApi'
import type { ClassGroup, Student } from './types'

const empty = { name: '', phone: '', parent_name: '', parent_phone: '', grade: '', class_ids: [] as number[] }

export default function Students() {
  const { has } = useAuth()
  const [search, setSearch] = useState('')
  const [classId, setClassId] = useState('')
  const q = new URLSearchParams({ ...(search && { search }), ...(classId && { class_id: classId }) }).toString()
  const students = useApi<{ data: Student[] }>(`/students${q ? `?${q}` : ''}`)
  const classes = useApi<{ data: ClassGroup[] }>('/classes')
  const [editing, setEditing] = useState<{ id?: number; form: typeof empty } | null>(null)
  const [errors, setErrors] = useState<Record<string, string[]>>({})

  function open(student?: Student) {
    setErrors({})
    setEditing(student
      ? { id: student.id, form: { name: student.name, phone: student.phone ?? '', parent_name: student.parent_name ?? '', parent_phone: student.parent_phone ?? '', grade: student.grade ?? '', class_ids: student.class_groups.map((c) => c.id) } }
      : { form: { ...empty } })
  }

  async function save(e: FormEvent) {
    e.preventDefault()
    if (!editing) return
    try {
      await api(editing.id ? `/students/${editing.id}` : '/students', { method: editing.id ? 'PATCH' : 'POST', body: editing.form })
      setEditing(null)
      await students.reload()
    } catch (err) {
      setErrors(err instanceof ApiError ? { ...err.fields, _: [err.message] } : { _: [(err as Error).message] })
    }
  }

  const form = editing?.form
  const update = (patch: Partial<typeof empty>) => editing && setEditing({ ...editing, form: { ...editing.form, ...patch } })

  return (
    <>
      <PageHeader title="Students" subtitle={students.data ? `${students.data.data.length} students` : undefined} actions={<Button onClick={() => open()}>Add student</Button>} />
      <div className="mb-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_240px]">
        <Input type="search" placeholder="Search by name or phone" aria-label="Search students" value={search} onChange={(e) => setSearch(e.target.value)} />
        <Select aria-label="Filter by class" value={classId} onChange={(e) => setClassId(e.target.value)}>
          <option value="">All classes</option>
          {classes.data?.data.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
        </Select>
      </div>
      {students.error && <ErrorBox message={students.error} />}
      {students.loading && !students.data ? <Loading /> : (
        <Card>
          <Table head={['Student', 'Classes', 'Parent', ...(has('fees') ? ['This month'] : [])]}>
            {students.data?.data.map((s) => (
              <tr key={s.id}>
                <td className="px-4 py-3">
                  <button type="button" onClick={() => open(s)} className="font-medium hover:underline">{s.name}</button>
                  <p className="text-xs text-slate-500">{[s.grade, s.phone].filter(Boolean).join(' · ')}</p>
                </td>
                <td className="px-4 py-3 text-slate-600">{s.class_groups.map((c) => c.name).join(', ') || '—'}</td>
                <td className="px-4 py-3 text-slate-600">{s.parent_name ?? '—'}<p className="text-xs text-slate-500">{s.parent_phone}</p></td>
                {has('fees') && <td className="px-4 py-3">{s.paid_this_month ? <Badge tone="green">Paid</Badge> : <Badge tone="amber">Due</Badge>}</td>}
              </tr>
            ))}
            {students.data?.data.length === 0 && <tr><td colSpan={4} className="px-4 py-8 text-center text-slate-500">No students yet.</td></tr>}
          </Table>
        </Card>
      )}

      {editing && form && (
        <Modal title={editing.id ? 'Edit student' : 'Add a student'} onClose={() => setEditing(null)}>
          <form onSubmit={save} className="grid gap-3">
            {errors._ && <ErrorBox message={errors._[0]} />}
            <Field label="Name" error={errors.name?.[0]}><Input required value={form.name} onChange={(e) => update({ name: e.target.value })} /></Field>
            <div className="grid gap-3 sm:grid-cols-2">
              <Field label="Phone" error={errors.phone?.[0]}><Input type="tel" value={form.phone} onChange={(e) => update({ phone: e.target.value })} /></Field>
              <Field label="Grade or batch" error={errors.grade?.[0]}><Input value={form.grade} onChange={(e) => update({ grade: e.target.value })} /></Field>
              <Field label="Parent name" error={errors.parent_name?.[0]}><Input value={form.parent_name} onChange={(e) => update({ parent_name: e.target.value })} /></Field>
              <Field label="Parent phone" error={errors.parent_phone?.[0]}><Input type="tel" value={form.parent_phone} onChange={(e) => update({ parent_phone: e.target.value })} /></Field>
            </div>
            <fieldset className="grid gap-1.5 text-sm">
              <legend className="mb-1 font-medium text-slate-700">Classes</legend>
              {classes.data?.data.map((c) => (
                <label key={c.id} className="flex items-center gap-2">
                  <input type="checkbox" className="h-4 w-4 accent-blue-600" checked={form.class_ids.includes(c.id)} onChange={(e) => update({ class_ids: e.target.checked ? [...form.class_ids, c.id] : form.class_ids.filter((x) => x !== c.id) })} />
                  {c.name}
                </label>
              ))}
            </fieldset>
            <div className="flex gap-2 pt-2"><Button type="submit">Save</Button><Button variant="ghost" onClick={() => setEditing(null)}>Cancel</Button></div>
          </form>
        </Modal>
      )}
    </>
  )
}
