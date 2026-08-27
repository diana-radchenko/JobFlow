<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    CalendarCheck,
    ChevronLeft,
    ExternalLink,
    Mail,
    X,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { getApplicationStatusColor } from '@/helpers/job-applications';
import { stringForHuman } from '@/helpers/strings';
import type { UserWorkJobApplication, WorkJob } from '@/types/laravel-models';
import {
    destroy as cancelInterviewRoute,
    store as scheduleInterviewRoute,
} from '@/actions/App/Http/Controllers/Employer/InterviewScheduleController';
import { store as sendJobMessageRoute } from '@/actions/App/Http/Controllers/JobChatController';
import applications from '@/routes/employer/applications';
import jobs from '@/routes/employer/jobs';

const props = defineProps<{
    job: WorkJob;
    application: UserWorkJobApplication;
    timezoneIdentifiers: string[];
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
const interviewParts = (value?: string | null, timezone = 'UTC') => {
    if (!value) {
        return { date: '', time: '' };
    }

    const parts = Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
        })
            .formatToParts(new Date(value))
            .map((part) => [part.type, part.value]),
    );

    return {
        date: `${parts.year}-${parts.month}-${parts.day}`,
        time: `${parts.hour}:${parts.minute}`,
    };
};
const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const savedTimezone =
    props.application.interview_session?.timezone ??
    (props.timezoneIdentifiers.includes(browserTimezone)
        ? browserTimezone
        : 'America/New_York');
const savedInterview = interviewParts(
    props.application.interview_session?.scheduled_at,
    savedTimezone,
);
const interviewForm = useForm({
    date: savedInterview.date,
    time: savedInterview.time,
    timezone: savedTimezone,
    duration_minutes:
        props.application.interview_session?.duration_minutes ?? 30,
    interview_format:
        props.application.interview_session?.interview_format ?? '',
    meeting_link: props.application.interview_session?.meeting_link ?? '',
    location: props.application.interview_session?.location ?? '',
    employer_note: props.application.interview_session?.employer_note ?? '',
});
const scheduleDialogOpen = ref(false);
const cancelDialogOpen = ref(false);
const scheduleInterview = () =>
    interviewForm.post(
        scheduleInterviewRoute([props.job.id, props.application.id]).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                scheduleDialogOpen.value = false;
            },
        },
    );
const cancelForm = useForm({});
const cancelInterview = () =>
    cancelForm.delete(
        cancelInterviewRoute([props.job.id, props.application.id]).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelDialogOpen.value = false;
            },
        },
    );
const messageForm = useForm({ body: '' });
const sendMessage = () =>
    messageForm.post(sendJobMessageRoute(props.application.id).url);

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
const formatInterviewDate = (date: string, timezone: string) =>
    new Intl.DateTimeFormat(undefined, {
        timeZone: timezone,
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(date));
const formatInterviewTime = (date: string, timezone: string) =>
    new Intl.DateTimeFormat(undefined, {
        timeZone: timezone,
        hour: 'numeric',
        minute: '2-digit',
        timeZoneName: 'short',
    }).format(new Date(date));
const formatLabel = (format?: string | null) =>
    format === 'in_person'
        ? 'In-person interview'
        : format
          ? `${format.charAt(0).toUpperCase()}${format.slice(1)} interview`
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

        <Card>
            <CardContent class="space-y-4 pt-6">
                <template
                    v-if="
                        props.application.interview_session?.status ===
                            'scheduled' &&
                        props.application.interview_session.scheduled_at
                    "
                >
                    <div>
                        <p class="font-semibold text-emerald-700">
                            Interview Scheduled
                        </p>
                        <p class="text-lg font-medium">
                            {{
                                formatInterviewDate(
                                    props.application.interview_session
                                        .scheduled_at,
                                    props.application.interview_session
                                        .timezone ?? 'UTC',
                                )
                            }}
                        </p>
                        <p>
                            {{
                                formatInterviewTime(
                                    props.application.interview_session
                                        .scheduled_at,
                                    props.application.interview_session
                                        .timezone ?? 'UTC',
                                )
                            }}
                        </p>
                        <p>
                            {{
                                props.application.interview_session
                                    .duration_minutes ?? 30
                            }}
                            minutes
                        </p>
                        <p
                            v-if="
                                props.application.interview_session
                                    .interview_format
                            "
                            class="mt-2 font-medium"
                        >
                            {{
                                formatLabel(
                                    props.application.interview_session
                                        .interview_format,
                                )
                            }}
                        </p>
                        <a
                            v-if="
                                props.application.interview_session.meeting_link
                            "
                            :href="
                                props.application.interview_session.meeting_link
                            "
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-primary underline underline-offset-4"
                        >
                            Meeting link <ExternalLink class="h-3.5 w-3.5" />
                        </a>
                        <p
                            v-if="props.application.interview_session.location"
                            class="text-sm text-foreground/70"
                        >
                            {{ props.application.interview_session.location }}
                        </p>
                        <p
                            v-if="
                                props.application.interview_session
                                    .employer_note
                            "
                            class="mt-2 text-sm text-foreground/70"
                        >
                            {{
                                props.application.interview_session
                                    .employer_note
                            }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="scheduleDialogOpen = true"
                        >
                            Reschedule
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            @click="cancelDialogOpen = true"
                        >
                            Cancel interview
                        </Button>
                    </div>
                </template>
                <template v-else>
                    <h2 class="text-lg font-semibold">Schedule an interview</h2>
                    <p class="text-sm text-foreground/60">
                        Choose a real date, time and timezone for this
                        candidate.
                    </p>
                    <Button type="button" @click="scheduleDialogOpen = true">
                        <CalendarCheck class="mr-2 h-4 w-4" />Schedule an
                        interview
                    </Button>
                </template>
            </CardContent>
        </Card>

        <Dialog v-model:open="scheduleDialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            props.application.interview_session?.status ===
                            'scheduled'
                                ? 'Reschedule interview'
                                : 'Schedule an interview'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        The selected local time is safely stored in UTC together
                        with its timezone.
                    </DialogDescription>
                </DialogHeader>
                <form
                    id="interview-form"
                    class="grid gap-4 sm:grid-cols-2"
                    @submit.prevent="scheduleInterview"
                >
                    <label class="space-y-1 text-sm">
                        <span>Interview date</span>
                        <input
                            v-model="interviewForm.date"
                            required
                            type="date"
                            class="w-full rounded-md border bg-background p-2"
                        />
                        <span
                            v-if="interviewForm.errors.date"
                            class="block text-xs text-destructive"
                            >{{ interviewForm.errors.date }}</span
                        >
                    </label>
                    <label class="space-y-1 text-sm">
                        <span>Start time</span>
                        <input
                            v-model="interviewForm.time"
                            required
                            type="time"
                            class="w-full rounded-md border bg-background p-2"
                        />
                        <span
                            v-if="interviewForm.errors.time"
                            class="block text-xs text-destructive"
                            >{{ interviewForm.errors.time }}</span
                        >
                    </label>
                    <label class="space-y-1 text-sm">
                        <span>Time zone</span>
                        <select
                            v-model="interviewForm.timezone"
                            required
                            class="w-full rounded-md border bg-background p-2"
                        >
                            <option
                                v-for="zone in props.timezoneIdentifiers"
                                :key="zone"
                                :value="zone"
                            >
                                {{ zone }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span>Duration (optional)</span>
                        <select
                            v-model="interviewForm.duration_minutes"
                            class="w-full rounded-md border bg-background p-2"
                        >
                            <option :value="30">30 minutes</option>
                            <option :value="45">45 minutes</option>
                            <option :value="60">60 minutes</option>
                            <option :value="90">90 minutes</option>
                        </select>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span>Interview format (optional)</span>
                        <select
                            v-model="interviewForm.interview_format"
                            class="w-full rounded-md border bg-background p-2"
                        >
                            <option value="">Not specified</option>
                            <option value="video">Video</option>
                            <option value="phone">Phone</option>
                            <option value="in_person">In person</option>
                        </select>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span>Meeting link (optional)</span>
                        <input
                            v-model="interviewForm.meeting_link"
                            type="url"
                            placeholder="https://..."
                            class="w-full rounded-md border bg-background p-2"
                        />
                    </label>
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span>Location (optional)</span>
                        <input
                            v-model="interviewForm.location"
                            maxlength="500"
                            placeholder="Office address or phone instructions"
                            class="w-full rounded-md border bg-background p-2"
                        />
                    </label>
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span>Employer note/message (optional)</span>
                        <textarea
                            v-model="interviewForm.employer_note"
                            maxlength="2000"
                            class="min-h-24 w-full rounded-md border bg-background p-2"
                        />
                    </label>
                </form>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Close</Button>
                    </DialogClose>
                    <Button
                        form="interview-form"
                        type="submit"
                        :disabled="interviewForm.processing"
                    >
                        Save interview
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="cancelDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cancel this interview?</DialogTitle>
                    <DialogDescription>
                        The meeting will be marked cancelled. Its history will
                        be retained and can be reviewed later.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Keep it</Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="cancelForm.processing"
                        @click="cancelInterview"
                    >
                        Cancel interview
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
