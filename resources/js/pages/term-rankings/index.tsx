import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

type RankingArticle = {
    id: number;
    name: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ranking de términos', href: route('term-rankings.index') }];

export default function TermRankingsIndex({ articles }: { articles: RankingArticle[] }) {
    const [selectedArticleIds, setSelectedArticleIds] = useState<number[]>([]);
    const [algorithm, setAlgorithm] = useState('counting-sort');
    const [processing, setProcessing] = useState(false);

    const toggleArticle = (articleId: number, checked: boolean | 'indeterminate') => {
        setSelectedArticleIds((currentIds) => {
            if (checked && !currentIds.includes(articleId)) {
                return [...currentIds, articleId];
            }

            if (!checked) {
                return currentIds.filter((currentId) => currentId !== articleId);
            }

            return currentIds;
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (selectedArticleIds.length < 2 || processing) {
            return;
        }

        setProcessing(true);
        router.get(
            route('term-rankings.results'),
            { article_ids: selectedArticleIds, algorithm },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ranking de términos" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header>
                    <h1 className="text-2xl font-semibold">Ranking de términos mediante algoritmos de ordenamiento</h1>
                    <p className="text-muted-foreground mt-2">
                        Selecciona al menos dos artículos científicos para analizar las palabras de sus resúmenes.
                    </p>
                </header>

                {articles.length === 0 ? (
                    <section className="rounded-xl border p-8 text-center">
                        <p className="font-medium">No hay artículos científicos registrados.</p>
                        <p className="text-muted-foreground mt-1 text-sm">Agrega artículos antes de generar un ranking.</p>
                    </section>
                ) : (
                    <form onSubmit={submit} className="flex max-w-4xl flex-col gap-6">
                        <fieldset className="space-y-3">
                            <legend className="mb-3 font-medium">Artículos científicos</legend>
                            {articles.map((article) => {
                                const checkboxId = `ranking-article-${article.id}`;

                                return (
                                    <div
                                        key={article.id}
                                        className="bg-card hover:bg-accent/50 flex items-start gap-3 rounded-lg border p-4 transition-colors"
                                    >
                                        <Checkbox
                                            id={checkboxId}
                                            checked={selectedArticleIds.includes(article.id)}
                                            onCheckedChange={(checked) => toggleArticle(article.id, checked)}
                                            className="mt-0.5"
                                        />
                                        <label htmlFor={checkboxId} className="cursor-pointer font-medium">
                                            {article.name}
                                        </label>
                                    </div>
                                );
                            })}
                        </fieldset>

                        <div className="grid max-w-sm gap-2">
                            <label htmlFor="ranking-algorithm" className="text-sm font-medium">
                                Algoritmo de ordenamiento
                            </label>
                            <select
                                id="ranking-algorithm"
                                value={algorithm}
                                onChange={(event) => setAlgorithm(event.target.value)}
                                className="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                            >
                                <option value="counting-sort">Counting Sort (ordenamiento por conteo)</option>
                            </select>
                        </div>

                        <div className="flex flex-wrap items-center gap-4">
                            <Button type="submit" disabled={selectedArticleIds.length < 2 || processing}>
                                {processing ? 'Calculando ranking…' : 'Generar ranking'}
                            </Button>
                            <p className="text-muted-foreground text-sm" aria-live="polite">
                                {selectedArticleIds.length} seleccionados · mínimo 2
                            </p>
                        </div>
                    </form>
                )}
            </main>
        </AppLayout>
    );
}
