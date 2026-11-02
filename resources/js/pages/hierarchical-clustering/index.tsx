import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type ClusteringArticle = {
    id: number;
    name: string;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agrupamiento Jerárquico', href: route('hierarchical-clustering.index') }];

export default function HierarchicalClusteringIndex({
    articles,
    embeddingAvailable,
}: {
    articles: ClusteringArticle[];
    embeddingAvailable: boolean;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agrupamiento Jerárquico" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header>
                    <h1 className="text-2xl font-semibold">Agrupamiento Jerárquico</h1>
                    <p className="text-muted-foreground mt-2 max-w-4xl leading-relaxed">
                        Se compararán los abstracts de todos los artículos sembrados usando sus embeddings FastText y distancia euclidiana. Single,
                        Complete y Average Linkage producirán un dendrograma cada uno.
                    </p>
                </header>

                {articles.length < 3 ? (
                    <section className="rounded-xl border p-8 text-center">
                        <p className="font-medium">Se requieren al menos tres artículos para evaluar silhouette.</p>
                    </section>
                ) : (
                    <>
                        <section className="bg-card flex flex-wrap items-center justify-between gap-4 rounded-xl border p-4">
                            <div>
                                <h2 className="font-semibold">Corpus del análisis</h2>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {articles.length} artículos · se analizarán todos, sin selección manual.
                                </p>
                            </div>
                            {embeddingAvailable ? (
                                <Button asChild>
                                    <Link href={route('hierarchical-clustering.results')}>Generar dendrogramas</Link>
                                </Button>
                            ) : (
                                <Button disabled>Embeddings FastText no disponibles</Button>
                            )}
                        </section>

                        {!embeddingAvailable && (
                            <p className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                                Genera primero el artefacto FastText según la documentación de vectorización.
                            </p>
                        )}

                        <fieldset className="space-y-3">
                            <legend className="mb-3 font-medium">Artículos incluidos</legend>
                            {articles.map((article) => (
                                <div key={article.id} className="bg-card flex items-center gap-3 rounded-lg border p-3">
                                    <Checkbox checked disabled aria-label="Artículo incluido en el análisis" />
                                    <span className="font-medium">{article.name}</span>
                                </div>
                            ))}
                        </fieldset>
                    </>
                )}
            </main>
        </AppLayout>
    );
}
