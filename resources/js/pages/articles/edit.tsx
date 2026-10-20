import AppLayout from '@/layouts/app-layout';
import ArticleForm, { type Article } from '@/pages/articles/article-form';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

export default function ArticlesEdit({ article }: { article: Article }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Artículos', href: route('articles.index') },
        { title: article.name, href: route('articles.show', article.id) },
        { title: 'Editar', href: route('articles.edit', article.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${article.name}`} />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Editar artículo</h1>
                    <p className="text-muted-foreground mt-1">Actualiza los datos de «{article.name}».</p>
                </div>
                <ArticleForm article={article} />
            </main>
        </AppLayout>
    );
}
