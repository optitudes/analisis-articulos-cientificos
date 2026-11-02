import { type ReactNode } from 'react';

type DendrogramArticle = {
    id: number;
    name: string;
};

type DendrogramMerge = {
    id: number;
    left: number;
    right: number;
    distance: number;
    size: number;
};

const clusterPalette = ['#2563eb', '#dc2626', '#059669', '#9333ea', '#d97706', '#0891b2', '#c026d3', '#4d7c0f', '#be123c', '#4338ca'];

/** Dibuja un árbol horizontal en SVG sin depender de una librería de gráficos. */
export function HierarchicalDendrogram({
    title,
    merges,
    articles,
    bestClusterCount,
    clusterAssignments,
}: {
    title: string;
    merges: DendrogramMerge[];
    articles: DendrogramArticle[];
    bestClusterCount: number;
    clusterAssignments: number[];
}) {
    const labelWidth = 360;
    const plotWidth = 760;
    const topPadding = 42;
    const rowHeight = 28;
    const chartHeight = topPadding + articles.length * rowHeight + 38;
    const maximumDistance = merges.reduce((largest, merge) => Math.max(largest, merge.distance), 0);
    const positions: Record<number, { x: number; y: number }> = {};
    const membersByNode: Record<number, number[]> = {};

    const distinctGroups: number[] = [];
    for (const clusterId of clusterAssignments) {
        if (!distinctGroups.includes(clusterId)) {
            distinctGroups.push(clusterId);
        }
    }
    const colorByGroup = new Map<number, string>();
    distinctGroups.forEach((clusterId, index) => {
        colorByGroup.set(clusterId, clusterPalette[index % clusterPalette.length]);
    });

    // Place leaves in article order; each internal node is centered between its children.
    articles.forEach((_, index) => {
        positions[index] = {
            x: labelWidth,
            y: topPadding + index * rowHeight + rowHeight / 2,
        };
        membersByNode[index] = [index];
    });

    const branches: ReactNode[] = [];

    for (const merge of merges) {
        const leftPosition = positions[merge.left];
        const rightPosition = positions[merge.right];
        const mergeX = labelWidth + (maximumDistance === 0 ? 0 : (merge.distance / maximumDistance) * plotWidth);
        const mergeY = (leftPosition.y + rightPosition.y) / 2;
        const mergedMembers = [...membersByNode[merge.left], ...membersByNode[merge.right]];
        const groupIds = new Set(mergedMembers.map((index) => clusterAssignments[index]));
        const branchColor = groupIds.size === 1 ? (colorByGroup.get(groupIds.values().next().value as number) ?? '#64748b') : '#64748b';

        branches.push(
            <g key={`merge-${merge.id}`}>
                <line x1={leftPosition.x} y1={leftPosition.y} x2={mergeX} y2={leftPosition.y} stroke={branchColor} />
                <line x1={rightPosition.x} y1={rightPosition.y} x2={mergeX} y2={rightPosition.y} stroke={branchColor} />
                <line x1={mergeX} y1={leftPosition.y} x2={mergeX} y2={rightPosition.y} stroke={branchColor} />
                <circle cx={mergeX} cy={mergeY} r="2.5" fill={branchColor} />
            </g>,
        );

        positions[merge.id] = { x: mergeX, y: mergeY };
        membersByNode[merge.id] = mergedMembers;
    }

    return (
        <section className="overflow-hidden rounded-xl border">
            <header className="border-b p-4">
                <h3 className="font-semibold">{title}</h3>
                <p className="text-muted-foreground mt-1 text-sm">
                    Corte seleccionado: {bestClusterCount} grupos · altura de fusión: distancia euclidiana
                </p>
            </header>
            <div className="overflow-x-auto p-3">
                <svg
                    role="img"
                    aria-label={`Dendrograma ${title} para ${articles.length} abstracts`}
                    width={labelWidth + plotWidth + 30}
                    height={chartHeight}
                    viewBox={`0 0 ${labelWidth + plotWidth + 30} ${chartHeight}`}
                    className="max-w-none text-xs"
                >
                    <text x={labelWidth} y="18" fill="#64748b">
                        Distancia de fusión
                    </text>
                    <line x1={labelWidth} y1={topPadding - 12} x2={labelWidth + plotWidth} y2={topPadding - 12} stroke="#cbd5e1" />
                    {Array.from({ length: 5 }, (_, tickIndex) => {
                        const ratio = tickIndex / 4;
                        const x = labelWidth + ratio * plotWidth;

                        return (
                            <g key={`tick-${tickIndex}`}>
                                <line x1={x} y1={topPadding - 16} x2={x} y2={topPadding - 8} stroke="#94a3b8" />
                                <text x={x} y={topPadding - 20} textAnchor="middle" fill="#64748b">
                                    {(maximumDistance * ratio).toFixed(2)}
                                </text>
                            </g>
                        );
                    })}

                    {articles.map((article, index) => {
                        const clusterColor = colorByGroup.get(clusterAssignments[index]) ?? '#64748b';
                        const displayedName = article.name.length > 48 ? `${article.name.slice(0, 45)}…` : article.name;

                        return (
                            <g key={article.id}>
                                <circle cx="12" cy={positions[index].y} r="4" fill={clusterColor} />
                                <text x="24" y={positions[index].y + 4} fill="#334155">
                                    <title>{article.name}</title>
                                    {displayedName}
                                </text>
                            </g>
                        );
                    })}

                    {branches}
                </svg>
            </div>
            <footer className="text-muted-foreground border-t px-4 py-2 text-xs">
                Los colores indican los grupos del corte elegido mediante silhouette.
            </footer>
        </section>
    );
}
