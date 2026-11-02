import { HierarchicalDendrogram } from '@/components/hierarchical-dendrogram';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type ClusteringArticle = {
    id: number;
    name: string;
    embeddingCoveragePercent: number | null;
};

type SilhouetteCut = {
    clusterCount: number;
    score: number;
};

type ClusteringAlgorithmResult = {
    algorithm: string;
    label: string;
    merges: { id: number; left: number; right: number; distance: number; size: number }[];
    bestClusterCount: number;
    bestScore: number;
    scoresByClusterCount: SilhouetteCut[];
    clusterAssignments: number[];
    executionTimeNanoseconds: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Agrupamiento Jerárquico', href: route('hierarchical-clustering.index') }];

export default function HierarchicalClusteringResults({
    articles,
    algorithms,
    recommendedAlgorithms,
    embeddingDimensions,
    distanceMetric,
}: {
    articles: ClusteringArticle[];
    algorithms: ClusteringAlgorithmResult[];
    recommendedAlgorithms: string[];
    embeddingDimensions: number;
    distanceMetric: string;
}) {
    const recommendedSet = new Set(recommendedAlgorithms);
    const executionTime = (nanoseconds: number) => (nanoseconds / 1_000_000).toFixed(3);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dendrogramas de Agrupamiento Jerárquico" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Dendrogramas de Agrupamiento Jerárquico</h1>
                        <p className="text-muted-foreground mt-2">
                            {articles.length} abstracts · embeddings FastText de {embeddingDimensions} dimensiones · {distanceMetric}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('hierarchical-clustering.index')}>Volver al análisis</Link>
                    </Button>
                </header>

                {recommendedAlgorithms.length > 0 && (
                    <section className="rounded-xl border border-emerald-500/40 bg-emerald-500/5 p-5">
                        <p className="text-sm font-semibold text-emerald-700">
                            {recommendedAlgorithms.length === 1 ? 'Mayor coherencia interna' : 'Empate en coherencia interna'}
                        </p>
                        <h2 className="mt-1 text-xl font-bold">
                            {algorithms
                                .filter((result) => recommendedSet.has(result.algorithm))
                                .map((result) => result.label)
                                .join(' · ')}
                        </h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Silhouette máximo {Math.max(...algorithms.map((result) => result.bestScore)).toFixed(3)}. Un valor mayor indica grupos más
                            compactos y separados en el mejor corte de cada método; un empate significa que la métrica no distingue un ganador.
                        </p>
                    </section>
                )}

                <section className="overflow-hidden rounded-xl border">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <caption className="sr-only">Comparación de los tres métodos mediante silhouette</caption>
                            <thead className="bg-muted/50 border-b">
                                <tr>
                                    <th scope="col" className="px-4 py-3 font-medium">
                                        Método
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-medium">
                                        Mejor k
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-medium">
                                        Silhouette
                                    </th>
                                    <th scope="col" className="px-4 py-3 text-right font-medium">
                                        Tiempo (ms)
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {algorithms.map((result) => (
                                    <tr key={result.algorithm}>
                                        <th scope="row" className="px-4 py-3 font-medium">
                                            {result.label}
                                            {recommendedSet.has(result.algorithm) && (
                                                <span className="ml-2 rounded-full bg-emerald-600/10 px-2 py-1 text-xs text-emerald-700">
                                                    {recommendedAlgorithms.length === 1 ? 'Mayor silhouette' : 'Empate'}
                                                </span>
                                            )}
                                        </th>
                                        <td className="px-4 py-3 text-right tabular-nums">{result.bestClusterCount}</td>
                                        <td className="px-4 py-3 text-right font-semibold tabular-nums">{result.bestScore.toFixed(4)}</td>
                                        <td className="px-4 py-3 text-right tabular-nums">{executionTime(result.executionTimeNanoseconds)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="text-muted-foreground border-t px-4 py-3 text-sm">
                        Se evaluaron cortes con k=2..{Math.max(2, articles.length - 1)}. El score silhouette está entre -1 y 1; un valor alto indica
                        mayor separación interna, no una verdad absoluta sobre las categorías científicas.
                    </p>
                </section>

                <section className="flex flex-col gap-5">
                    <header>
                        <h2 className="text-xl font-semibold">Dendrogramas</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Cada unión muestra la distancia euclidiana a la que se fusionaron los dos grupos.
                        </p>
                    </header>
                    {algorithms.map((result) => (
                        <HierarchicalDendrogram
                            key={result.algorithm}
                            title={result.label}
                            merges={result.merges}
                            articles={articles}
                            bestClusterCount={result.bestClusterCount}
                            clusterAssignments={result.clusterAssignments}
                        />
                    ))}
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="font-semibold">Cobertura de embeddings</h2>
                    <p className="text-muted-foreground mt-1 text-sm">Porcentaje de términos de cada abstract que tienen vector FastText.</p>
                    <ul className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                        {articles.map((article) => (
                            <li key={article.id} className="flex justify-between gap-4">
                                <span className="truncate" title={article.name}>
                                    {article.name}
                                </span>
                                <span className="shrink-0 font-medium tabular-nums">
                                    {article.embeddingCoveragePercent === null ? 'Sin términos' : `${article.embeddingCoveragePercent}%`}
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            </main>
        </AppLayout>
    );
}
