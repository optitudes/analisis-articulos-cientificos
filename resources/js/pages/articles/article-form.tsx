import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export type Article = {
    id: number;
    name: string;
    abstract: string | null;
    relative_path: string;
    path: string;
    created_at: string;
    updated_at: string;
};

export default function ArticleForm({ article }: { article: Article }) {
    const { data, setData, put, errors, processing } = useForm({
        name: article.name,
        abstract: article.abstract ?? '',
        relative_path: article.relative_path,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        put(route('articles.update', article.id));
    };

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <div className="grid gap-2">
                <Label htmlFor="name">Nombre</Label>
                <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} required maxLength={255} />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="abstract">Resumen</Label>
                <textarea
                    id="abstract"
                    value={data.abstract}
                    onChange={(event) => setData('abstract', event.target.value)}
                    rows={8}
                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden"
                    placeholder="El resumen puede completarse más adelante."
                />
                <InputError message={errors.abstract} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="relative_path">Ruta relativa del archivo</Label>
                <Input
                    id="relative_path"
                    value={data.relative_path}
                    onChange={(event) => setData('relative_path', event.target.value)}
                    required
                    maxLength={65535}
                    placeholder="articles/mi-articulo.pdf"
                />
                <p className="text-muted-foreground text-sm">La URL pública se genera automáticamente a partir de APP_URL.</p>
                <InputError message={errors.relative_path} />
            </div>

            <div className="flex flex-wrap gap-3">
                <Button type="submit" disabled={processing}>
                    Guardar cambios
                </Button>
                <Button variant="outline" asChild>
                    <Link href={route('articles.show', article.id)}>Cancelar</Link>
                </Button>
            </div>
        </form>
    );
}
