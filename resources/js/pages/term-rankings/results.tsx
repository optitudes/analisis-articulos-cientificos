import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

type RankedTerm = {
    term: string;
    frequency: number;
};

type RankingArticle = {
    id: number;
    name: string;
};

type RankingAlgorithm = 'counting-sort' | 'radix-sort' | 'bucket-sort';

const algorithmLabels: Record<RankingAlgorithm, string> = {
    'counting-sort': 'Counting Sort',
    'radix-sort': 'Radix Sort',
    'bucket-sort': 'Bucket Sort',
};

export default function TermRankingsResults({
    ranking,
    algorithm,
    executionTimeNanoseconds,
    articles,
}: {
    ranking: RankedTerm[];
    algorithm: RankingAlgorithm;
    executionTimeNanoseconds: number;
    articles: RankingArticle[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ranking de términos', href: route('term-rankings.index') }];
    const executionTimeMilliseconds = (executionTimeNanoseconds / 1_000_000).toFixed(3);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resultados del ranking de términos" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Ranking de términos</h1>
                        <p className="text-muted-foreground mt-2">
                            {algorithmLabels[algorithm]} · mayor frecuencia primero · empate en orden alfabético.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('term-rankings.index')}>Elegir otros artículos</Link>
                    </Button>
                </header>

                <section className="grid gap-4 rounded-xl border p-4 sm:grid-cols-2">
                    <div>
                        <h2 className="text-sm font-medium">Artículos analizados</h2>
                        <ul className="text-muted-foreground mt-2 space-y-1 text-sm">
                            {articles.map((article) => (
                                <li key={article.id}>{article.name}</li>
                            ))}
                        </ul>
                    </div>
                    <div>
                        <h2 className="text-sm font-medium">Tiempo de ejecución del algoritmo</h2>
                        <p className="mt-2 text-2xl font-semibold tabular-nums">{executionTimeMilliseconds} ms</p>
                        <p className="text-muted-foreground mt-1 text-xs">No incluye la consulta ni el procesamiento previo de los resúmenes.</p>
                    </div>
                </section>

                {ranking.length === 0 ? (
                    <section className="rounded-xl border p-8 text-center">
                        <p className="font-medium">No se encontraron términos para ordenar.</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Los artículos seleccionados no contienen resúmenes con palabras procesables.
                        </p>
                    </section>
                ) : (
                    <section className="overflow-hidden rounded-xl border">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <caption className="sr-only">Términos científicos ordenados por frecuencia</caption>
                                <thead className="bg-muted/50 border-b">
                                    <tr>
                                        <th scope="col" className="w-20 px-4 py-3 font-medium">
                                            Posición
                                        </th>
                                        <th scope="col" className="px-4 py-3 font-medium">
                                            Término
                                        </th>
                                        <th scope="col" className="w-40 px-4 py-3 text-right font-medium">
                                            Frecuencia
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {ranking.map((item, index) => (
                                        <tr key={item.term}>
                                            <td className="text-muted-foreground px-4 py-3 tabular-nums">{index + 1}</td>
                                            <th scope="row" className="px-4 py-3 font-medium">
                                                {item.term}
                                            </th>
                                            <td className="px-4 py-3 text-right tabular-nums">{item.frequency}</td>
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
