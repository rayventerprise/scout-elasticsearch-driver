<?php

namespace ScoutElastic\Indexers;

use Illuminate\Database\Eloquent\Collection;
use ScoutElastic\Facades\ElasticClient;
use ScoutElastic\Migratable;
use ScoutElastic\Payloads\RawPayload;
use ScoutElastic\Payloads\TypePayload;

class BulkIndexer implements IndexerInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(Collection $models)
    {
        $model = $models->first();
        $indexConfigurator = $model->getIndexConfigurator();

        $bulkPayload = new TypePayload($model);

        if (in_array(Migratable::class, class_uses_recursive($indexConfigurator))) {
            $bulkPayload->useAlias('write');
        }

        if ($documentRefresh = config('scout_elastic.document_refresh')) {
            $bulkPayload->set('refresh', $documentRefresh);
        }

        /** Preload any eager relationships.. */
        $newModels = $this->withSearchableUsingModels(get_class($model), $models->pluck('id')->toArray());

        $newModels->each(function ($newModel) use ($bulkPayload) {
            if ($newModel::usesSoftDelete() && config('scout.soft_delete', false)) {
                $newModel->pushSoftDeleteMetadata();
            }

            $modelData = array_merge(
                $newModel->toSearchableArray(),
                $newModel->scoutMetadata()
            );

            if (empty($modelData)) {
                return true;
            }

            $actionPayload = (new RawPayload)
                ->set('index._id', $newModel->getScoutKey());

            $bulkPayload
                ->add('body', $actionPayload->get())
                ->add('body', $modelData);
        });

        ElasticClient::bulk($bulkPayload->get());
    }

    /**
     * Provide massive performance improvements as we will properly tap into eager loading..
     */
    public function withSearchableUsingModels($class, $modelIds) {
        $self = new $class;

        $softDelete = $class::usesSoftDelete() && config('scout.soft_delete', false);

        return $self->newQuery()
            ->whereIn('id', $modelIds)
            ->when(true, function ($query) use ($self) {
                try {
                    $self->makeAllSearchableUsing($query);
                } catch ( \Exception $e) {
                    // do nothing, method_exists returns incorrect.
                }
            })
            ->when($softDelete, function ($query) {
                $query->withTrashed();
            })
            ->orderBy($self->getKeyName())
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Collection $models)
    {
        $model = $models->first();

        $bulkPayload = new TypePayload($model);

        $models->each(function ($model) use ($bulkPayload) {
            $actionPayload = (new RawPayload)
                ->set('delete._id', $model->getScoutKey());

            $bulkPayload->add('body', $actionPayload->get());
        });

        if ($documentRefresh = config('scout_elastic.document_refresh')) {
            $bulkPayload->set('refresh', $documentRefresh);
        }

        $bulkPayload->set('client.ignore', 404);

        ElasticClient::bulk($bulkPayload->get());
    }
}
