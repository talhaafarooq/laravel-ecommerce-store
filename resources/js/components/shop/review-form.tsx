import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { RatingInput } from '@/components/shop/rating-stars';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/reviews';
import type { Review } from '@/types';

export function ReviewForm({
    slug,
    existing,
}: {
    slug: string;
    existing: Review | null;
}) {
    const [rating, setRating] = useState(existing?.rating ?? 0);

    return (
        <Form
            {...store.form(slug)}
            options={{ preserveScroll: true }}
            resetOnSuccess={['title', 'body']}
            className="space-y-4 rounded-xl border bg-card p-5"
        >
            {({ processing, errors }) => (
                <>
                    <div>
                        <h3 className="font-semibold">
                            {existing ? 'Update your review' : 'Write a review'}
                        </h3>
                        <p className="text-sm text-muted-foreground">
                            Share your experience to help other shoppers.
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label>Your rating</Label>
                        <RatingInput value={rating} onChange={setRating} />
                        <InputError message={errors.rating} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="review-title">Title (optional)</Label>
                        <Input
                            id="review-title"
                            name="title"
                            maxLength={120}
                            defaultValue={existing?.title ?? ''}
                            placeholder="Sum it up in a few words"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="review-body">Your review</Label>
                        <Textarea
                            id="review-body"
                            name="body"
                            rows={4}
                            required
                            defaultValue={existing?.body ?? ''}
                            placeholder="What did you like or dislike? How did you use it?"
                        />
                        <InputError message={errors.body} />
                    </div>

                    <Button type="submit" disabled={processing || rating === 0}>
                        {existing ? 'Update review' : 'Submit review'}
                    </Button>
                </>
            )}
        </Form>
    );
}
