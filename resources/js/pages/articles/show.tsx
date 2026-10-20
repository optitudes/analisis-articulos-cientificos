import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { type Article } from './article-form';

export default function ArticlesShow({ article }: { article: Article }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Artículos', href: route('articles.index') },
        { title: article.name, href: route('articles.show', article.id) },
    ];

    const destroy = () => {
        if (window.confirm(`¿Eliminar el artículo «${article.name}»?`)) {
            router.delete(route('articles.destroy', article.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={article.name} />
            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">{article.name}</h1>
                        <p className="text-muted-foreground mt-2 text-sm">{article.relative_path}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('articles.edit', article.id)}>Editar</Link>
                        </Button>
                        <Button variant="destructive" onClick={destroy}>
                            Eliminar
                        </Button>
                    </div>
                </header>

                <section className="max-w-4xl space-y-6 rounded-xl border p-5">
                    <div>
                        <h2 className="font-medium">Resumen</h2>
                        <p className="text-muted-foreground mt-2 whitespace-pre-wrap">{article.abstract || 'Sin resumen.'}</p>
                    </div>
                    <div>
                        <h2 className="font-medium">Archivo</h2>
                        <a href={article.path} target="_blank" rel="noreferrer" className="text-primary mt-2 inline-block underline">
                            Abrir artículo (PDF)
                        </a>
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}
