<?php

namespace Suitcoda\Model;

use Suitcoda\Model\Inspection;
use Suitcoda\Model\Issue;
use Suitcoda\Model\Scope;
use Suitcoda\Model\Url;

class JobInspect extends BaseModel
{
    protected $table = 'job_inspects';

    protected $fillable = [
        'command_line',
        'status',
        'issue_count'
    ];

    /**
     * Get the inspection for the current jobInspect.
     *
     * @return object
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * Get the scope for the current jobInspect.
     *
     * @return object
     */
    public function scope()
    {
        return $this->belongsTo(Scope::class);
    }

    /**
     * Get the url for the current jobInspect.
     *
     * @return object
     */
    public function url()
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the issues for the current jobInspect.
     *
     * @return object
     */
    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Scope a query to get unhandled job.
     *
     * @param  string $query []
     * @return object
     */
    public function scopeGetUnhandledJob($query)
    {
        return $query->where('status', 0)->get();
    }

    public function scopeCompleted($query)
    {
        $query->where('status', '2');
    }

    public function scopeByCategoryName($query, $name)
    {
        return $query->where('status', '2')->wherehas('scope', function ($query) use ($name) {
            $query->whereHas('category', function ($query) use ($name) {
                $query->where('name', $name);
            });
        });
    }
}
