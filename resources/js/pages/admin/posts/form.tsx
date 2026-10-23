import { Form, Head, Link } from '@inertiajs/react';
import { Field, FormSection } from '@/components/admin/form';
import { PageHeader } from '@/components/admin/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { withAdminLayout } from '@/layouts/admin-layout';
import admin from '@/routes/admin';
import type { Post } from '@/types';

/** The app's own wall-clock time for a datetime input (the server sends its own offset). */
const toInput = (value: string | null): string =>
    value ? value.slice(0, 16) : '';

export default function PostForm({ post }: { post: Post | null }) {
    const editing = post !== null;
    const action = editing
        ? admin.posts.update.form(post.id)
        : admin.posts.store.form();

    return (
        <>
            <Head title={editing ? `Edit ${post.title}` : 'New post'} />
            <PageHeader
                title={editing ? 'Edit post' : 'New post'}
                description="Write in Markdown. Leave the publish date empty to keep it as a draft, or set a future date to schedule it."
            />

            <Form
                {...action}
                options={{ preserveScroll: true }}
                className="space-y-6"
                encType="multipart/form-data"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6 xl:grid-cols-[1fr_22rem]">
                        <div className="space-y-6">
                            <FormSection title="Content">
                                <Field label="Title" error={errors.title}>
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="title"
                                            defaultValue={post?.title}
                                            required
                                            maxLength={200}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="Excerpt"
                                    error={errors.excerpt}
                                    hint="A short teaser shown in lists and search results."
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="excerpt"
                                            defaultValue={post?.excerpt ?? ''}
                                            rows={2}
                                            maxLength={500}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="Body"
                                    error={errors.body}
                                    hint="Markdown: ## headings, **bold**, lists, > quotes and [links](/shop)."
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="body"
                                            defaultValue={post?.body ?? ''}
                                            rows={22}
                                            required
                                            className="font-mono text-sm"
                                        />
                                    )}
                                </Field>
                            </FormSection>

                            <FormSection title="Search engines">
                                <Field
                                    label="Meta title"
                                    error={errors.meta_title}
                                    hint="Up to 70 characters."
                                >
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="meta_title"
                                            defaultValue={
                                                post?.meta_title ?? ''
                                            }
                                            maxLength={70}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="Meta description"
                                    error={errors.meta_description}
                                    hint="Up to 160 characters."
                                >
                                    {(id) => (
                                        <Textarea
                                            id={id}
                                            name="meta_description"
                                            defaultValue={
                                                post?.meta_description ?? ''
                                            }
                                            rows={2}
                                            maxLength={160}
                                        />
                                    )}
                                </Field>
                            </FormSection>
                        </div>

                        <aside className="space-y-6">
                            <FormSection title="Publishing">
                                <Field
                                    label="Publish date"
                                    error={errors.published_at}
                                    hint="Empty means draft."
                                >
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="published_at"
                                            type="datetime-local"
                                            defaultValue={toInput(
                                                post?.published_at ?? null,
                                            )}
                                        />
                                    )}
                                </Field>
                                <Field
                                    label="URL slug"
                                    error={errors.slug}
                                    hint="Blank to generate from the title."
                                >
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="slug"
                                            defaultValue={post?.slug}
                                            maxLength={220}
                                        />
                                    )}
                                </Field>
                            </FormSection>

                            <FormSection
                                title="Cover image"
                                description="Cropped to 16:9. JPG, PNG or WebP."
                            >
                                {post?.cover && (
                                    <img
                                        src={post.cover}
                                        alt=""
                                        className="aspect-[16/9] w-full rounded-lg border object-cover"
                                    />
                                )}
                                <Field
                                    label={
                                        post?.cover
                                            ? 'Replace image'
                                            : 'Upload image'
                                    }
                                    error={errors.cover}
                                >
                                    {(id) => (
                                        <Input
                                            id={id}
                                            name="cover"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                        />
                                    )}
                                </Field>
                                {post?.cover && (
                                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <input
                                            type="checkbox"
                                            name="remove_cover"
                                            value="1"
                                            className="size-4 accent-primary"
                                        />
                                        Remove current cover
                                    </label>
                                )}
                            </FormSection>

                            <div className="flex items-center justify-end gap-3">
                                <Button variant="outline" asChild>
                                    <Link href={admin.posts.index()}>
                                        Cancel
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {editing ? 'Save post' : 'Create post'}
                                </Button>
                            </div>
                        </aside>
                    </div>
                )}
            </Form>
        </>
    );
}

PostForm.layout = withAdminLayout({
    title: 'Blog posts',
    href: admin.posts.index(),
});
