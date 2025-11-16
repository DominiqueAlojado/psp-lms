import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import InServiceExamsLayout from '@/layouts/exams/inservice-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { FileClock, Plus } from 'lucide-react';
import { type Paginated } from '@/types';

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
							<Link href="/in-service/create">
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
								<div key={exam.id} className="flex items-center justify-between rounded border p-4">
									<div>
										<div className="flex items-center gap-2">
											<span className="font-medium">{exam.title}</span>
											<span className="rounded bg-muted px-2 py-0.5 text-xs">
												{exam.exam_year} – {exam.exam_period}
											</span>
										</div>
										<div className="mt-1 text-xs text-muted-foreground">
											{exam.questions_count} questions · {exam.total_points} points · Pass {exam.passing_score}
										</div>
									</div>
									<div className="flex items-center gap-2">
										<Button asChild size="sm" variant="outline">
											<Link href={`/inservice-exams/${exam.id}/edit`}>Edit</Link>
										</Button>
									</div>
								</div>
							))}
						</div>
					)}
				</div>
			</InServiceExamsLayout>
		</AppLayout>
	);
}
