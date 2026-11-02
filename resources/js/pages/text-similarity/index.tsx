import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';

type SimilarityArticle = {
    id: number;
    name: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Análisis de similitud textual', href: route('text-similarity.index') }];

export default function TextSimilarityIndex({
    articles,
    availableEmbeddingModels,
}: {
    articles: SimilarityArticle[];
    availableEmbeddingModels: { word2vec: boolean; fasttext: boolean };
}) {
    const [selectedArticleIds, setSelectedArticleIds] = useState<number[]>([]);
    const [algorithm, setAlgorithm] = useState('levenshtein');
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
            route('text-similarity.results'),
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
            <Head title="Análisis de similitud textual" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header>
                    <h1 className="text-2xl font-semibold">Análisis de similitud textual</h1>
                    <p className="text-muted-foreground mt-2">Selecciona al menos dos artículos para comparar los términos de sus resúmenes.</p>
                </header>

                {articles.length === 0 ? (
                    <section className="rounded-xl border p-8 text-center">
                        <p className="font-medium">No hay artículos científicos registrados.</p>
                        <p className="text-muted-foreground mt-1 text-sm">Agrega artículos antes de analizar similitudes.</p>
                    </section>
                ) : (
                    <form onSubmit={submit} className="flex max-w-4xl flex-col gap-6">
                        <section className="bg-card flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-end sm:justify-between">
                            <div className="grid gap-2 sm:min-w-96 sm:flex-1">
                                <label htmlFor="similarity-algorithm" className="text-sm font-medium">
                                    Algoritmo de comparación
                                </label>
                                <select
                                    id="similarity-algorithm"
                                    value={algorithm}
                                    onChange={(event) => setAlgorithm(event.target.value)}
                                    className="border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:outline-hidden"
                                >
                                    <option value="levenshtein">Algoritmo de Levenshtein</option>
                                    <option value="needleman-wunsch">Needleman–Wunsch</option>
                                    <option value="tf-idf-cosine">Similitud del Coseno (usando TF-IDF)</option>
                                    <option value="jaccard">Coeficiente de Jaccard</option>
                                    <option value="word2vec-cosine" disabled={!availableEmbeddingModels.word2vec}>
                                        Word2Vec preentrenado + coseno
                                        {!availableEmbeddingModels.word2vec ? ' (requiere generar embeddings)' : ''}
                                    </option>
                                    <option value="fasttext-cosine" disabled={!availableEmbeddingModels.fasttext}>
                                        FastText preentrenado + coseno
                                        {!availableEmbeddingModels.fasttext ? ' (requiere generar embeddings)' : ''}
                                    </option>
                                    <option value="word2vec-euclidean" disabled={!availableEmbeddingModels.word2vec}>
                                        Word2Vec preentrenado + distancia euclidiana
                                        {!availableEmbeddingModels.word2vec ? ' (requiere generar embeddings)' : ''}
                                    </option>
                                    <option value="fasttext-euclidean" disabled={!availableEmbeddingModels.fasttext}>
                                        FastText preentrenado + distancia euclidiana
                                        {!availableEmbeddingModels.fasttext ? ' (requiere generar embeddings)' : ''}
                                    </option>
                                </select>
                                {(!availableEmbeddingModels.word2vec || !availableEmbeddingModels.fasttext) && (
                                    <p className="text-muted-foreground text-xs leading-relaxed">
                                        Para habilitar Word2Vec y FastText, exporta el corpus y genera el archivo local de embeddings. Consulta la
                                        documentación técnica.
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-wrap items-center gap-4">
                                <Button type="submit" disabled={selectedArticleIds.length < 2 || processing}>
                                    {processing ? 'Calculando similitudes…' : 'Comparar artículos'}
                                </Button>
                                <p className="text-muted-foreground text-sm" aria-live="polite">
                                    {selectedArticleIds.length} seleccionados · mínimo 2
                                </p>
                            </div>
                        </section>

                        <fieldset className="space-y-3">
                            <legend className="mb-3 font-medium">Artículos científicos</legend>
                            {articles.map((article) => {
                                const checkboxId = `similarity-article-${article.id}`;

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
                    </form>
                )}
            </main>
        </AppLayout>
    );
}
