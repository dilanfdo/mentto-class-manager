import { useState, type FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Badge, Button, Card, ErrorBox, Field, Input, Loading, Modal, PageHeader, Select, Table, statusTone } from '../../components/ui'
import { api, ApiError } from '../../lib/api'
import { lkr, monthLabel, thisMonth } from '../../lib/format'
import { useApi } from '../../lib/useApi'
import type { ClassGroup, Payment, Student } from './types'

const METHOD = { bank: 'Bank slip', cash: 'Cash', card: 'Card' }

export default function Fees() {
  const [params, setParams] = useSearchParams()
  const month = params.get('month') ?? thisMonth()
  const status = params.get('status') ?? ''
  const q = new URLSearchParams({ month, ...(status && { status }) }).toString()
  const payments = useApi<{ data: Payment[] }>(`/payments?${q}`)
  const students = useApi<{ data: Student[] }>('/students')
  const classes = useApi<{ data: ClassGroup[] }>('/classes')
  const [adding, setAdding] = useState(false)
  const [form, setForm] = useState({ student_id: '', class_group_id: '', amount: '', method: 'cash', month })
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [busy, setBusy] = useState<number | null>(null)

  const setFilter = (key: string, value: string) => {
    const next = new URLSearchParams(params)
    if (value) next.set(key, value)
    else next.delete(key)
    setParams(next)
  }

  async function approve(p: Payment) {
    setBusy(p.id)
    try {
      await api(`/payments/${p.id}/approve`, { method: 'POST' })
      await payments.reload()
    } finally {
      setBusy(null)
    }
  }

  async function save(e: FormEvent) {
    e.preventDefault()
    try {
      await api('/payments', { method: 'POST', body: { ...form, student_id: Number(form.student_id), class_group_id: Number(form.class_group_id), amount: Number(form.amount) } })
      setAdding(false)
      await payments.reload()
    } catch (err) {
      setErrors(err instanceof ApiError ? { ...err.fields, _: [err.message] } : { _: [(err as Error).message] })
    }
  }

  const student = students.data?.data.find((s) => s.id === Number(form.student_id))
  const studentClasses = classes.data?.data.filter((c) => student?.class_groups.some((sc) => sc.id === c.id)) ?? []
  const total = payments.data?.data.filter((p) => p.status === 'approved').reduce((sum, p) => sum + p.amount, 0) ?? 0

  return (
    <>
      <PageHeader title="Fees" subtitle={`${monthLabel(month)} · ${lkr(total)} approved`} actions={<Button onClick={() => { setErrors({}); setForm({ student_id: '', class_group_id: '', amount: '', method: 'cash', month }); setAdding(true) }}>Record payment</Button>} />
      <div className="mb-4 grid gap-2 sm:grid-cols-[200px_200px]">
        <Input type="month" aria-label="Month" value={month} onChange={(e) => setFilter('month', e.target.value)} />
        <Select aria-label="Filter by status" value={status} onChange={(e) => setFilter('status', e.target.value)}>
          <option value="">All payments</option>
          <option value="pending">Slips to check</option>
          <option value="approved">Approved</option>
        </Select>
      </div>
      {payments.error && <ErrorBox message={payments.error} />}
      {payments.loading && !payments.data ? <Loading /> : (
        <Card>
          <Table head={['Student', 'Class', 'Amount', 'Paid by', 'Status', '']}>
            {payments.data?.data.map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-3 font-medium">{p.student.name}</td>
                <td className="px-4 py-3 text-slate-600">{p.class_group.name}</td>
                <td className="whitespace-nowrap px-4 py-3 tabular-nums">{lkr(p.amount)}</td>
                <td className="px-4 py-3 text-slate-600">{METHOD[p.method]}</td>
                <td className="px-4 py-3"><Badge tone={statusTone(p.status)}>{p.status === 'pending' ? 'To check' : 'Approved'}</Badge></td>
                <td className="px-4 py-3 text-right">{p.status === 'pending' && <Button className="px-2.5 py-1 text-xs" disabled={busy === p.id} onClick={() => void approve(p)}>Approve</Button>}</td>
              </tr>
            ))}
            {payments.data?.data.length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">No payments for this month yet.</td></tr>}
          </Table>
        </Card>
      )}

      {adding && (
        <Modal title="Record a payment" onClose={() => setAdding(false)}>
          <form onSubmit={save} className="grid gap-3">
            {errors._ && <ErrorBox message={errors._[0]} />}
            <Field label="Student" error={errors.student_id?.[0]}>
              <Select required value={form.student_id} onChange={(e) => setForm({ ...form, student_id: e.target.value, class_group_id: '', amount: '' })}>
                <option value="">Choose a student</option>
                {students.data?.data.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </Select>
            </Field>
            <Field label="Class" error={errors.class_group_id?.[0]}>
              <Select required value={form.class_group_id} onChange={(e) => setForm({ ...form, class_group_id: e.target.value, amount: String(classes.data?.data.find((c) => c.id === Number(e.target.value))?.monthly_fee ?? '') })}>
                <option value="">{student ? 'Choose a class' : 'Choose a student first'}</option>
                {studentClasses.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </Select>
            </Field>
            <div className="grid gap-3 sm:grid-cols-3">
              <Field label="Amount (LKR)" error={errors.amount?.[0]}><Input required type="number" min={1} value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} /></Field>
              <Field label="Month" error={errors.month?.[0]}><Input required type="month" value={form.month} onChange={(e) => setForm({ ...form, month: e.target.value })} /></Field>
              <Field label="Paid by" error={errors.method?.[0]}>
                <Select value={form.method} onChange={(e) => setForm({ ...form, method: e.target.value })}><option value="cash">Cash</option><option value="bank">Bank slip</option><option value="card">Card</option></Select>
              </Field>
            </div>
            <p className="text-xs text-slate-500">Cash is approved straight away. Bank slips wait in "Slips to check".</p>
            <div className="flex gap-2 pt-2"><Button type="submit">Save payment</Button><Button variant="ghost" onClick={() => setAdding(false)}>Cancel</Button></div>
          </form>
        </Modal>
      )}
    </>
  )
}
