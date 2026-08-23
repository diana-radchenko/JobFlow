<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarCheck, ChevronLeft, Mail, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type { UserWorkJobApplication, WorkJob } from '@/types/laravel-models';
import applications from '@/routes/employer/applications';
import jobs from '@/routes/employer/jobs';

const props = defineProps<{
    job: WorkJob;
    application: UserWorkJobApplication;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'My Jobs',
                href: '/employer/jobs',
            },
            {
                title: 'Application',
            },
        ],
    },
});

const form = useForm({ status: props.application.status });
const interviewForm = useForm({
    date: props.application.interview_session?.scheduled_at?.slice(0, 10) ?? '',
    time: props.application.interview_session?.scheduled_at
        ? new Date(
              props.application.interview_session.scheduled_at,
          ).toLocaleTimeString('en-GB', {
              hour: '2-digit',
              minute: '2-digit',
              hour12: false,
          })
        : '',
    timezone:
        props.application.interview_session?.timezone ?? 'America/New_York',
    duration_minutes:
        props.application.interview_session?.duration_minutes ?? 30,
    employer_note: props.application.interview_session?.employer_note ?? '',
});
const timezones = [
    'America/New_York',
    'America/Chicago',
    'America/Denver',
    'America/Los_Angeles',
    'Europe/London',
    'Europe/Brussels',
    'Europe/Moscow',
    'Asia/Dubai',
    'Asia/Singapore',
    'Asia/Tokyo',
];
const scheduleInterview = () =>
    interviewForm.post(
        `/employer/jobs/${props.job.id}/applications/${props.application.id}/interview`,
        { preserveScroll: true },
    );
const messageForm = useForm({ body: '' });
const sendMessage = () =>
    messageForm.post(`/job-chat/applications/${props.application.id}`);

const setStatus = (status: 'rejected' | 'interview_scheduled') => {
    form.status = status;
    form.patch(applications.update.url([props.job.id, props.application.id]), {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) =>
    date
        ? new Date(date).toLocaleDateString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
          })
        : '';
</script>

<template>
    <Head title="Application" />

    <div class="mx-auto max-w-5xl space-y-6 p-6">
        <Link
            :href="jobs.show(props.job.id)"
            class="inline-flex items-center gap-1 text-sm text-foreground/60 transition-colors hover:text-foreground"
        >
            <ChevronLeft class="h-4 w-4" />
            Back to {{ props.job.title }}
        </Link>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold">
                    {{
                        props.application.user?.name ??
                        props.application.user?.email
                    }}
                </h1>
                <p class="text-sm text-foreground/60">
                    Applied to {{ props.job.title }} on
                    {{ formatDate(props.application.created_at) }}
                </p>
            </div>
            <span
                :class="`rounded-full px-3 py-1 text-xs font-medium ${getApplicationStatusColor(props.application.status)}`"
            >
                {{ stringForHuman(props.application.status) }}
            </span>
        </div>

        <Card>
            <CardContent class="space-y-3 pt-6 text-sm">
                <div class="flex items-center gap-2">
                    <Mail class="h-4 w-4 text-foreground/60" />
                    <a
                        :href="`mailto:${props.application.user?.email}`"
                        class="underline underline-offset-4"
                    >
                        {{ props.application.user?.email }}
                    </a>
                </div>
                <p class="text-foreground/60">
                    Viewed on {{ formatDate(props.application.viewed_at) }}
                </p>
            </CardContent>
        </Card>

        <Card v-if="props.application.resume">
            <CardContent class="space-y-5 pt-6 text-sm">
                <h2 class="text-lg font-semibold">
                    Resume: {{ props.application.resume.title }}
                </h2>

                <div v-if="props.application.resume.skills.length">
                    <h3 class="mb-2 font-medium text-foreground/80">Skills</h3>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="skill in props.application.resume.skills"
                            :key="skill.id"
                            class="rounded-full bg-accent px-3 py-1 text-xs"
                        >
                            {{ skill.name }} ({{
                                stringForHuman(skill.proficiency_level)
                            }})
                        </span>
                    </div>
                </div>

                <div v-if="props.application.resume.work_experiences.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Work Experience
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="experience in props.application.resume
                                .work_experiences"
                            :key="experience.id"
                        >
                            <p class="font-medium">
                                {{ experience.job_title }} —
                                {{ experience.company_name }}
                            </p>
                            <p class="text-xs text-foreground/60">
                                {{ formatDate(experience.start_date) }} –
                                {{
                                    experience.is_current
                                        ? 'Present'
                                        : formatDate(experience.end_date)
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="props.application.resume.educations.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Education
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="education in props.application.resume
                                .educations"
                            :key="education.id"
                        >
                            <p class="font-medium">
                                {{ stringForHuman(education.degree) }},
                                {{ education.field_of_study }}
                            </p>
                            <p class="text-xs text-foreground/60">
                                {{ education.institution }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="props.application.resume.projects.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Projects
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="project in props.application.resume.projects"
                            :key="project.id"
                        >
                            <p class="font-medium">{{ project.title }}</p>
                            <p
                                v-if="project.description"
                                class="text-xs text-foreground/60"
                            >
                                {{ project.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.application.resume.volunteer_experiences.length"
                >
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Volunteer Experience
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="volunteer in props.application.resume
                                .volunteer_experiences"
                            :key="volunteer.id"
                        >
                            <p class="font-medium">
                                {{ volunteer.role }} —
                                {{ volunteer.organization }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.application.resume.leadership_activities.length"
                >
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Leadership Activities
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="activity in props.application.resume
                                .leadership_activities"
                            :key="activity.id"
                        >
                            <p class="font-medium">
                                {{ activity.role }} —
                                {{ activity.organization }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="props.application.resume.publications.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Publications
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="publication in props.application.resume
                                .publications"
                            :key="publication.id"
                        >
                            <p class="font-medium">{{ publication.title }}</p>
                            <p
                                v-if="publication.publisher"
                                class="text-xs text-foreground/60"
                            >
                                {{ publication.publisher }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="props.application.resume.award_honors.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Awards &amp; Honors
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="award in props.application.resume
                                .award_honors"
                            :key="award.id"
                        >
                            <p class="font-medium">{{ award.title }}</p>
                            <p
                                v-if="award.issuer"
                                class="text-xs text-foreground/60"
                            >
                                {{ award.issuer }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="props.application.resume.languages.length">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Languages
                    </h3>
                    <p>
                        {{
                            props.application.resume.languages
                                .map(
                                    (language) =>
                                        `${language.name} — ${language.proficiency}`,
                                )
                                .join(', ')
                        }}
                    </p>
                </div>

                <div v-if="props.application.resume.additional_information">
                    <h3 class="mb-2 font-medium text-foreground/80">
                        Additional Information
                    </h3>
                    <p
                        v-if="
                            props.application.resume.additional_information
                                .certifications
                        "
                    >
                        Certifications:
                        {{
                            props.application.resume.additional_information
                                .certifications
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>
        <p v-else class="text-sm text-foreground/60">
            No resume was attached to this application.
        </p>

        <Card
            ><CardContent class="space-y-4 pt-6"
                ><h2 class="text-lg font-semibold">Schedule an interview</h2>
                <form
                    class="grid gap-3 sm:grid-cols-2"
                    @submit.prevent="scheduleInterview"
                >
                    <input
                        v-model="interviewForm.date"
                        required
                        type="date"
                        class="rounded-md border bg-background p-2"
                    />
                    <input
                        v-model="interviewForm.time"
                        required
                        type="time"
                        class="rounded-md border bg-background p-2"
                    />
                    <select
                        v-model="interviewForm.timezone"
                        class="rounded-md border bg-background p-2"
                    >
                        <option v-for="zone in timezones" :key="zone">
                            {{ zone }}
                        </option>
                    </select>
                    <select
                        v-model="interviewForm.duration_minutes"
                        class="rounded-md border bg-background p-2"
                    >
                        <option :value="30">30 minutes</option>
                        <option :value="45">45 minutes</option>
                        <option :value="60">60 minutes</option>
                        <option :value="90">90 minutes</option>
                    </select>
                    <textarea
                        v-model="interviewForm.employer_note"
                        placeholder="Optional instructions"
                        class="rounded-md border bg-background p-2 sm:col-span-2"
                    />
                    <Button type="submit" :disabled="interviewForm.processing"
                        ><CalendarCheck class="mr-2 h-4 w-4" />{{
                            props.application.interview_session
                                ? 'Reschedule interview'
                                : 'Schedule an interview'
                        }}</Button
                    >
                </form>
                <p
                    v-if="props.application.interview_session?.scheduled_at"
                    class="text-sm text-emerald-700"
                >
                    Interview scheduled:
                    {{
                        new Date(
                            props.application.interview_session.scheduled_at,
                        ).toLocaleString()
                    }}
                    · {{ props.application.interview_session.timezone }}
                </p>
            </CardContent></Card
        >

        <Card
            ><CardContent class="space-y-3 pt-6"
                ><h2 class="font-semibold">Message candidate</h2>
                <form class="flex gap-2" @submit.prevent="sendMessage">
                    <input
                        v-model="messageForm.body"
                        required
                        maxlength="5000"
                        class="flex-1 rounded-md border bg-background p-2"
                        placeholder="Write a message about this application"
                    /><Button type="submit">Send</Button>
                </form></CardContent
            ></Card
        >

        <div class="flex flex-wrap gap-3">
            <Button
                type="button"
                variant="destructive"
                :disabled="
                    form.processing || props.application.status === 'rejected'
                "
                @click="setStatus('rejected')"
            >
                <X class="mr-2 h-4 w-4" />
                Reject
            </Button>
        </div>
    </div>
</template>
