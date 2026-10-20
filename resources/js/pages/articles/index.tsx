import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { type Article } from './article-form';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Artículos', href: route('articles.index') }];

export default function ArticlesIndex({ articles }: { articles: Article[] }) {
    const destroy = (article: Article) => {
        if (window.confirm(`¿Eliminar el artículo «${article.name}»?`)) {
            router.delete(route('articles.destroy', article.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Artículos" />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Artículos</h1>
                        <p className="text-muted-foreground mt-1">Administra los artículos y sus archivos.</p>
                    </div>
                </header>

                {articles.length === 0 ? (
                    <div className="rounded-xl border p-8 text-center">
                        <p className="text-muted-foreground">Aún no hay artículos registrados.</p>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border">
                        <div className="divide-y">
                            {articles.map((article) => (
                                <article key={article.id} className="flex flex-col justify-between gap-4 p-4 sm:flex-row sm:items-center">
                                    <div className="min-w-0">
                                        <Link href={route('articles.show', article.id)} className="font-medium hover:underline">
                                            {article.name}
                                        </Link>
                                        <p className="text-muted-foreground mt-1 truncate text-sm">{article.relative_path}</p>
                                        {article.abstract && <p className="mt-2 line-clamp-2 text-sm">{article.abstract}</p>}
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        <Button variant="outline" size="sm" asChild>
                                            <a href={article.path} target="_blank" rel="noreferrer">
                                                Ver documento
                                            </a>
                                        </Button>
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={route('articles.edit', article.id)}>Editar</Link>
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => destroy(article)}>
                                            Eliminar
                                        </Button>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </div>
                )}
            </main>
        </AppLayout>
    );
}
