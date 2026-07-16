import { AssessmentLogsSheet } from '@/components/inservice-exams/assessment-logs-sheet';
import HeadingSmall from '@/components/heading-small';
import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import InServiceExamsLayout from '@/layouts/exams/inservice-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileClock, FileText, Plus } from 'lucide-react';
import { type Paginated } from '@/types';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'In-Service Exams',
		href: '/inservice-exams/active',
	},
];

interface DraftExam {
	id: number;
	title: string;
	description: string | null;
	exam_year: number;
	exam_period: string;
	questions_count: number;
	total_points: number;
	passing_score: number;
	duration_minutes: number | null;
	is_published: boolean;
	created_by: string | null;
	created_at: string | null;
}

interface PageProps {
	exams: Paginated<DraftExam>;
	filters: { search?: string };
}

export default function Drafts() {
	const { exams } = usePage<PageProps>().props;
	const [deletingId, setDeletingId] = useState<number | null>(null);
	const [deletingTitle, setDeletingTitle] = useState<string | undefined>(undefined);
	const [viewingLogsAssessment, setViewingLogsAssessment] = useState<{
		id: number;
		title: string;
	} | null>(null);

	const askDelete = (exam: DraftExam) => {
		setDeletingId(exam.id);
		setDeletingTitle(exam.title);
	};

	const confirmDelete = () => {
		if (deletingId === null) return;
		router.delete(`/inservice-exams/${deletingId}`, {
			preserveScroll: true,
			onSuccess: () => {
				toast.success('Exam deleted');
				router.reload({ only: ['exams'] });
			},
			onError: () => {
				toast.error('Failed to delete exam');
			},
			onFinish: () => {
				setDeletingId(null);
				setDeletingTitle(undefined);
			},
		});
	};

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title="In-Service Exams – Drafts" />

			<InServiceExamsLayout>
				<div className="space-y-6">
					<div className="flex items-center justify-between">
						<HeadingSmall
							title="Draft In-Service Exams"
							description="Exams saved as drafts"
						/>
						<Button asChild>
							<Link href="/inservice-exams/create">
								<Plus className="mr-2 h-4 w-4" />
								Create National Exam
							</Link>
						</Button>
					</div>

					{exams.data.length === 0 ? (
						<div className="rounded-lg border p-6 text-center">
							<FileClock className="mx-auto h-10 w-10 text-muted-foreground" />
							<p className="mt-2 text-sm text-muted-foreground">
								No draft in-service exams yet.
							</p>
						</div>
					) : (
						<div className="space-y-3">
							{exams.data.map((exam) => (
								<div
									key={exam.id}
									className="flex items-center justify-between rounded-[1.35rem] border border-border/80 bg-[linear-gradient(180deg,color-mix(in_oklab,var(--color-card)_97%,white),color-mix(in_oklab,var(--color-card)_94%,var(--color-accent)))] p-4 shadow-[0_16px_30px_-28px_rgb(35_24_74_/_0.12)] transition-[border-color,box-shadow,transform] hover:-translate-y-0.5 hover:border-primary/15 hover:shadow-[0_22px_40px_-30px_rgb(96_44_193_/_0.22)] dark:shadow-[0_18px_32px_-28px_rgb(0_0_0_/_0.42)]"
								>
									<div>
										<div className="flex items-center gap-2">
											<span className="font-medium">{exam.title}</span>
											<span className="rounded-full border border-border/70 bg-background/88 px-2.5 py-0.5 text-xs font-medium text-foreground">
												{exam.exam_year} – {exam.exam_period}
											</span>
										</div>
										<div className="mt-1 text-xs text-muted-foreground">
											{exam.questions_count} questions · {exam.total_points} points · MPL {exam.passing_score}
										</div>
									</div>
									<div className="flex items-center gap-2">
										<Button
											size="sm"
											variant="outline"
											onClick={() =>
												setViewingLogsAssessment({
													id: exam.id,
													title: exam.title,
												})
											}
										>
											<FileText className="mr-1 h-3 w-3" />
											Logs
										</Button>
										<Button asChild size="sm" variant="outline">
											<Link href={`/inservice-exams/${exam.id}/edit`}>Edit</Link>
										</Button>
										<Button size="sm" variant="destructive" onClick={() => askDelete(exam)}>
											Delete
										</Button>
									</div>
								</div>
							))}
						</div>
					)}
				</div>
			</InServiceExamsLayout>
			<DeleteConfirmationDialog
				open={deletingId !== null}
				title="Delete Exam?"
				itemIdentifier={deletingId !== null ? `Exam #${deletingId}` : undefined}
				itemName={deletingTitle}
				warningMessage="This action cannot be undone. This will permanently delete the exam and all its questions."
				confirmText="Delete Exam"
				onConfirm={confirmDelete}
				onCancel={() => {
					setDeletingId(null);
					setDeletingTitle(undefined);
				}}
			/>

			{/* Assessment Logs Sheet */}
			<AssessmentLogsSheet
				assessment={viewingLogsAssessment}
				open={!!viewingLogsAssessment}
				onOpenChange={(open) => {
					if (!open) {
						setViewingLogsAssessment(null);
					}
				}}
			/>
		</AppLayout>
	);
}
