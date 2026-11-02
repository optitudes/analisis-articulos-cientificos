import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type SimilarityArticle = {
    id: number;
    name: string;
    embeddingCoveragePercent?: number | null;
};

type AlgorithmKey =
    | 'levenshtein'
    | 'needleman-wunsch'
    | 'tf-idf-cosine'
    | 'jaccard'
    | 'word2vec-cosine'
    | 'fasttext-cosine'
    | 'word2vec-euclidean'
    | 'fasttext-euclidean';
type MetricDirection = 'lower' | 'higher';

const algorithmLabels: Record<AlgorithmKey, string> = {
    levenshtein: 'Algoritmo de Levenshtein',
    'needleman-wunsch': 'Needleman–Wunsch',
    'tf-idf-cosine': 'Similitud del Coseno (usando TF-IDF)',
    jaccard: 'Coeficiente de Jaccard',
    'word2vec-cosine': 'Word2Vec preentrenado + coseno',
    'fasttext-cosine': 'FastText preentrenado + coseno',
    'word2vec-euclidean': 'Word2Vec preentrenado + distancia euclidiana',
    'fasttext-euclidean': 'FastText preentrenado + distancia euclidiana',
};

export default function TextSimilarityResults({
    algorithm,
    metric,
    metricDirection,
    matrix,
    visualSimilarityMatrix,
    embeddingDimensions,
    executionTimeNanoseconds,
    articles,
}: {
    algorithm: AlgorithmKey;
    metric: string;
    metricDirection: MetricDirection;
    matrix: number[][];
    visualSimilarityMatrix: number[][];
    embeddingDimensions: number | null;
    executionTimeNanoseconds: number;
    articles: SimilarityArticle[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Análisis de similitud textual', href: route('text-similarity.index') }];
    const executionTimeMilliseconds = (executionTimeNanoseconds / 1_000_000).toFixed(3);
    const directionDescription = metricDirection === 'lower' ? 'Un valor menor indica mayor similitud.' : 'Un valor mayor indica mayor similitud.';
    const usesEuclideanDistance = algorithm === 'word2vec-euclidean' || algorithm === 'fasttext-euclidean';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resultados de similitud textual" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Análisis de similitud textual</h1>
                        <p className="text-muted-foreground mt-2">
                            {algorithmLabels[algorithm]} · {metric}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('text-similarity.index')}>Elegir otros artículos</Link>
                    </Button>
                </header>

                <section className="grid gap-6 rounded-xl border p-4 sm:grid-cols-2 sm:p-6">
                    <div>
                        <h2 className="text-base font-semibold">Artículos analizados</h2>
                        <ul className="text-muted-foreground mt-3 space-y-1 text-sm">
                            {articles.map((article) => (
                                <li key={article.id}>
                                    {article.name}
                                    {article.embeddingCoveragePercent !== undefined && article.embeddingCoveragePercent !== null && (
                                        <span className="ml-1">· cobertura de términos {article.embeddingCoveragePercent}%</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div>
                        <h2 className="text-base font-semibold">Cómo interpretar los resultados</h2>
                        <p className="text-muted-foreground mt-2 text-base leading-relaxed md:text-lg">
                            {directionDescription} El color representa una escala visual normalizada: verde indica máxima similitud, amarillo una
                            similitud intermedia y rojo una similitud baja.
                        </p>
                        {embeddingDimensions !== null && (
                            <p className="text-muted-foreground mt-2 text-sm">
                                Cada abstracto está representado por un embedding de {embeddingDimensions} dimensiones.
                            </p>
                        )}
                        {usesEuclideanDistance && (
                            <p className="text-muted-foreground mt-2 text-sm">
                                Los colores se normalizan respecto de la distancia máxima entre los artículos seleccionados; los números muestran la
                                distancia euclidiana original.
                            </p>
                        )}
                        <p className="text-muted-foreground mt-2 text-sm">
                            La tabla conserva el puntaje original del algoritmo. El color sirve como referencia visual y no modifica ese valor.
                        </p>
                        <div className="mt-5">
                            <div
                                role="img"
                                aria-label="Escala de color: rojo para baja similitud, amarillo para similitud intermedia y verde para máxima similitud"
                                className="h-4 rounded-full border"
                                style={{
                                    background: 'linear-gradient(90deg, hsl(0 75% 82%), hsl(60 75% 82%), hsl(120 65% 78%))',
                                }}
                            />
                            <div className="text-muted-foreground mt-1 flex justify-between gap-2 text-xs sm:text-sm">
                                <span>Rojo · baja</span>
                                <span>Amarillo · intermedia</span>
                                <span>Verde · máxima</span>
                            </div>
                        </div>
                        <div className="mt-5 border-t pt-4">
                            <h3 className="text-sm font-medium">Tiempo de ejecución del algoritmo</h3>
                            <p className="mt-1 text-xl font-semibold tabular-nums">{executionTimeMilliseconds} ms</p>
                            <p className="text-muted-foreground mt-1 text-sm">No incluye la consulta ni el preprocesamiento.</p>
                        </div>
                    </div>
                </section>

                {articles.length === 0 ? (
                    <section className="rounded-xl border p-8 text-center">
                        <p className="font-medium">No se encontraron artículos para comparar.</p>
                    </section>
                ) : (
                    <section className="overflow-hidden rounded-xl border">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <caption className="sr-only">Matriz de {metric} entre artículos</caption>
                                <thead className="bg-muted/50 border-b">
                                    <tr>
                                        <th scope="col" className="bg-muted/50 sticky left-0 px-4 py-3 font-medium">
                                            Artículo
                                        </th>
                                        {articles.map((article) => (
                                            <th key={article.id} scope="col" className="min-w-40 px-4 py-3 font-medium">
                                                {article.name}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {articles.map((article, rowIndex) => (
                                        <tr key={article.id}>
                                            <th scope="row" className="bg-background sticky left-0 px-4 py-3 font-medium">
                                                {article.name}
                                            </th>
                                            {articles.map((columnArticle, columnIndex) => (
                                                <td
                                                    key={columnArticle.id}
                                                    className="px-3 py-3 text-center font-medium tabular-nums sm:px-4"
                                                    style={{
                                                        backgroundColor: `hsl(${visualSimilarityMatrix[rowIndex][columnIndex] * 120} 70% 84%)`,
                                                    }}
                                                    title={`Similitud visual: ${(visualSimilarityMatrix[rowIndex][columnIndex] * 100).toFixed(1)}%`}
                                                >
                                                    {Number.isInteger(matrix[rowIndex][columnIndex])
                                                        ? matrix[rowIndex][columnIndex]
                                                        : matrix[rowIndex][columnIndex].toFixed(4)}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                )}
            </main>
        </AppLayout>
    );
}
