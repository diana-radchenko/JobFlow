<?php

namespace Database\Factories;

use App\Models\WorkJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkJob>
 */
class WorkJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $salaryStart = $this->faker->numberBetween(40000, 80000);
        $salaryEnd = $salaryStart + $this->faker->numberBetween(10000, 50000);

        return [
            'title' => $this->faker->jobTitle(),
            'salary_start' => $salaryStart,
            'salary_end' => $salaryEnd,
            'company' => $this->faker->company(),
            'description' => $this->faker->paragraphs(3, true),
            'contacts' => $this->faker->email(),
            'location' => $this->faker->city().', '.$this->faker->country(),
            'technologies' => $this->faker->randomElements(
                ['PHP', 'Laravel', 'React', 'Vue.js', 'JavaScript', 'TypeScript', 'MySQL', 'PostgreSQL', 'Node.js', 'Tailwind CSS', 'Docker', 'AWS', 'Git', 'GitHub', 'GitLab', 'Bitbucket', 'Jira', 'Confluence', 'Slack', 'Zoom', 'Microsoft Teams', 'Google Meet', 'Google Calendar', 'Google Docs', 'Google Sheets', 'Google Slides', 'Google Forms', 'Google Sites', 'Google Drive', 'Google Calendar', 'Google Docs', 'Google Sheets', 'Google Slides', 'Google Forms', 'Google Sites', 'Google Drive'],
                $this->faker->numberBetween(2, 5)
            ),
        ];
    }

    public static function mockReadyData(): array
    {
        return [
            [
                'title' => 'Software Engineer',
                'salary_start' => 85000,
                'salary_end' => 120000,
                'company' => 'TechCorp Solutions',
                'description' => 'We are looking for an experienced Software Engineer to join our growing team. You will work on building scalable backend systems using modern technologies. Responsibilities include designing APIs, optimizing database queries, and mentoring junior developers. The ideal candidate has 5+ years of experience and is passionate about clean code.',
                'contacts' => 'careers@techcorp.com',
                'location' => 'San Francisco, USA',
                'technologies' => ['PHP', 'Laravel', 'PostgreSQL', 'Docker', 'AWS', 'Git'],
            ],
            [
                'title' => 'Frontend Developer',
                'salary_start' => 75000,
                'salary_end' => 110000,
                'company' => 'Creative Studios',
                'description' => 'Join our frontend team to build beautiful and responsive user interfaces. You will work with React and modern JavaScript to create engaging web applications. We value attention to detail, user-centric design, and continuous learning. Experience with TypeScript and Tailwind CSS is a plus.',
                'contacts' => 'jobs@creativestudios.com',
                'location' => 'New York, USA',
                'technologies' => ['React', 'JavaScript', 'TypeScript', 'Tailwind CSS', 'Vue.js', 'GitHub'],
            ],
            [
                'title' => 'UI/UX Designer',
                'salary_start' => 65000,
                'salary_end' => 95000,
                'company' => 'Design Hub Agency',
                'description' => 'We are seeking a talented UI/UX Designer to create intuitive and visually appealing digital experiences. You will collaborate with product managers and developers to translate user needs into elegant solutions. Portfolio demonstrating your design process is required. Knowledge of design systems and accessibility standards is essential.',
                'contacts' => 'design@designhub.com',
                'location' => 'Austin, USA',
                'technologies' => ['Figma', 'Adobe XD', 'Sketch', 'Prototyping', 'User Research'],
            ],
            [
                'title' => 'Copywriter',
                'salary_start' => 50000,
                'salary_end' => 75000,
                'company' => 'Content Marketing Inc',
                'description' => 'Craft compelling copy that drives engagement and conversions. You will write for multiple channels including website, email campaigns, social media, and blogs. We need someone with a strong portfolio who can adapt tone and style to different audiences. SEO knowledge is a bonus.',
                'contacts' => 'hiring@contentmarketing.com',
                'location' => 'Remote',
                'technologies' => ['SEO', 'Content Strategy', 'Analytics', 'Mailchimp', 'WordPress'],
            ],
            [
                'title' => 'Social Media Manager (SMM)',
                'salary_start' => 45000,
                'salary_end' => 70000,
                'company' => 'Brand Boost Digital',
                'description' => 'Manage and grow our social media presence across all major platforms. You will create engaging content, interact with followers, and analyze performance metrics. Experience with social media tools and content calendars is essential. We are looking for someone creative and data-driven.',
                'contacts' => 'apply@brandbooost.com',
                'location' => 'Los Angeles, USA',
                'technologies' => ['Facebook', 'Instagram', 'Twitter', 'LinkedIn', 'Buffer', 'Hootsuite'],
            ],
            [
                'title' => 'Marketing Manager',
                'salary_start' => 70000,
                'salary_end' => 105000,
                'company' => 'Global Marketing Group',
                'description' => 'Lead marketing campaigns and strategies for our diverse portfolio of clients. You will oversee budget allocation, team coordination, and performance tracking. We need someone with 5+ years of marketing experience and proven track record of successful campaigns.',
                'contacts' => 'marketing@globalmktg.com',
                'location' => 'Chicago, USA',
                'technologies' => ['HubSpot', 'Google Analytics', 'Salesforce', 'Marketing Automation', 'CRM'],
            ],
            [
                'title' => 'QA Engineer',
                'salary_start' => 60000,
                'salary_end' => 90000,
                'company' => 'Quality Assurance Systems',
                'description' => 'Ensure product quality through comprehensive testing and automation. You will develop test plans, execute manual and automated tests, and report bugs. Experience with Selenium, Cypress, or similar frameworks is required. We value attention to detail and strong problem-solving skills.',
                'contacts' => 'qa@qasystems.com',
                'location' => 'Seattle, USA',
                'technologies' => ['Selenium', 'Cypress', 'Jest', 'Postman', 'Jira', 'Git'],
            ],
            [
                'title' => 'DevOps Engineer',
                'salary_start' => 90000,
                'salary_end' => 130000,
                'company' => 'Cloud Infrastructure Co',
                'description' => 'Design and maintain our cloud infrastructure and deployment pipelines. You will work with Docker, Kubernetes, and CI/CD tools. We need someone passionate about automation and infrastructure as code. Experience with AWS or similar cloud platforms is required.',
                'contacts' => 'devops@cloudinfra.com',
                'location' => 'Remote',
                'technologies' => ['Docker', 'Kubernetes', 'AWS', 'CI/CD', 'Terraform', 'Jenkins'],
            ],
            [
                'title' => 'Product Manager',
                'salary_start' => 95000,
                'salary_end' => 140000,
                'company' => 'Innovation Labs',
                'description' => 'Drive product vision and strategy for our next-generation platform. You will work closely with engineering, design, and marketing teams. We seek someone with strong analytical skills and experience shipping successful products. MBA or equivalent experience preferred.',
                'contacts' => 'careers@innovationlabs.com',
                'location' => 'Boston, USA',
                'technologies' => ['Product Strategy', 'Analytics', 'User Research', 'Roadmapping', 'Agile'],
            ],
            [
                'title' => 'Data Analyst',
                'salary_start' => 70000,
                'salary_end' => 105000,
                'company' => 'Analytics Intelligence',
                'description' => 'Transform raw data into actionable insights for business decisions. You will create dashboards, perform statistical analysis, and identify trends. Proficiency with SQL and visualization tools is essential. Experience with Python or R is highly desired.',
                'contacts' => 'analytics@anintelligence.com',
                'location' => 'Denver, USA',
                'technologies' => ['SQL', 'Python', 'Tableau', 'Power BI', 'Google Analytics', 'Statistics'],
            ],
        ];
    }
}
