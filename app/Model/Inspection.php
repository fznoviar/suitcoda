<?php

namespace Suitcoda\Model;

use Carbon\Carbon;
use Suitcoda\Model\Issue;
use Suitcoda\Model\JobInspect;
use Suitcoda\Model\Project;
use Suitcoda\Model\Score;
use Suitcoda\Model\SubScope;

class Inspection extends BaseModel
{
    const STATUS_WAITING = 0;
    const STATUS_ON_PROGRESS = 1;
    const STATUS_COMPLETED = 2;

    protected $table = 'inspections';

    protected $fillable = [
        'sequence_number',
        'scopes',
        'status',
        'score',
    ];

    /**
     * Get the project for the current inspection.
     *
     * @return object
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the jobInspects for the current inspection.
     *
     * @return object
     */
    public function jobInspects()
    {
        return $this->hasMany(JobInspect::class);
    }

    /**
     * Get the scores for the current inspection.
     *
     * @return object
     */
    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get the issues for the current inspection.
     *
     * @return object
     */
    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get score attribute with format
     *
     * @return string
     */
    public function getScoreAttribute()
    {
        if ($this->attributes['score']) {
            return (float)$this->attributes['score'] * 100;
        }
        return '-';
    }

    /**
     * Get status attribute in string
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        if ($this->attributes['status'] == self::STATUS_WAITING) {
            return 'Waiting';
        }
        if ($this->attributes['status'] == self::STATUS_ON_PROGRESS) {
            return 'On Progress';
        }
        if ($this->attributes['status'] == self::STATUS_COMPLETED) {
            return 'Completed';
        }
        return 'Stopped';
    }

    /**
     * Get status text color label attribute in string
     *
     * @return string
     */
    public function getStatusTextColorAttribute()
    {
        if ($this->attributes['status'] == self::STATUS_WAITING) {
            return 'grey';
        }
        if ($this->attributes['status'] == self::STATUS_ON_PROGRESS) {
            return 'orange';
        }
        if ($this->attributes['status'] == self::STATUS_COMPLETED) {
            return 'green';
        }
        return 'red';
    }

    /**
     * Get updated_at attribute in new format
     *
     * @param string $value []
     * @return string
     */
    public function getUpdatedAtAttribute($value)
    {
        $time = new Carbon($value);
        return $time->diffForHumans();
    }

    /**
     * Get used scopes in current inspection
     *
     * @return array
     */
    public function getScopeListAttribute()
    {
        $scopes = (int)$this->attributes['scopes'];
        foreach (SubScope::all() as $subScope) {
            if (($scopes & $subScope->code) > 0) {
                $scopeList[] = $subScope->scope->category;
            }
        }
        return collect(array_unique($scopeList));
    }

    /**
     * Get related last inspection score by category
     *
     * @param string $slug []
     * @return string
     */
    public function getScoreByCategory($slug)
    {
        $scoreByCategory = $this->scores()->byCategorySlug($slug)->first();
        if ($scoreByCategory) {
            return $scoreByCategory->score . '%';
        }
        return '-';
    }

    /**
     * Get related inspection total issue
     *
     * @return string
     */
    public function getIssuesAttribute()
    {
        if ($this->isCompleted()) {
            return $this->issues()->get()->count();
        }
    }

    /**
     * Scope a query to get inspections of a given sequence number.
     *
     * @param  string $query []
     * @param  string $number []
     * @return object
     */
    public function scopeBySequenceNumber($query, $number)
    {
        return $query->where('sequence_number', $number);
    }

    /**
     * Related query to get inspections of a given category name.
     *
     * @param  string $category []
     * @return object|null
     */
    public function getIssueListByCategory($category)
    {
        if ($this->isCompleted()) {
            return $this->issues()->byCategorySlug($category)->get();
        }
        return null;
    }

    /**
     * Scope a query to get inspections of a given id.
     *
     * @param  string $query []
     * @param  string $keyId []
     * @return object
     */
    public function scopeGetById($query, $keyId)
    {
        return $query->where('id', $keyId)->get();
    }

    /**
     * Scope a query to get completed inspections order desc.
     *
     * @param  string $query []
     * @return object
     */
    public function scopeLatestCompleted($query)
    {
        return $query->completed()->latest();
    }

    /**
     * Scope a query to get completed inspections.
     *
     * @param  string $query []
     * @return object
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Get inspection issue error count
     *
     * @return int
     */
    public function getIssueErrorAttribute()
    {
        if ($this->isCompleted()) {
            return $this->issues()->where('type', 'error')->get()->count();
        }
    }

    /**
     * Get inspection issue warning count
     *
     * @return int
     */
    public function getIssueWarningAttribute()
    {
        if ($this->isCompleted()) {
            return $this->issues()->where('type', 'warning')->get()->count();
        }
    }

    /**
     * Check if inspection status is completed
     *
     * @return bool
     */
    public function isCompleted()
    {
        if ($this->attributes['status'] == self::STATUS_COMPLETED) {
            return true;
        }
        return false;
    }

    /**
     * Check if inspection status is waiting
     *
     * @return bool
     */
    public function isWaiting()
    {
        if ($this->attributes['status'] == self::STATUS_WAITING) {
            return true;
        }
        return false;
    }

    /**
     * Check if inspection status is on progress
     *
     * @return bool
     */
    public function isProgress()
    {
        if ($this->attributes['status'] == self::STATUS_ON_PROGRESS) {
            return true;
        }
        return false;
    }

    /**
     * Get scope query of on progress inspection
     *
     * @param string $query []
     * @return bool
     */
    public function scopeProgress($query)
    {
        return $query->where('status', self::STATUS_ON_PROGRESS);
    }

    /**
     * Get number of unique url in issues by category
     *
     * @param  string $slug []
     * @return int
     */
    public function uniqueUrlIssueByCategory($slug)
    {
        return $this->issues()->byCategorySlug($slug)->error()->distinct()->select(['url', 'scope_id'])
                    ->get()->count();
    }

    /**
     * Get number of unique url in jobInspects by category
     *
     * @param  string $slug []
     * @return int
     */
    public function uniqueUrlJobByCategory($slug)
    {
        return $this->jobInspects()->byCategorySlug($slug)->distinct()->select(['url_id', 'scope_id'])
                    ->get()->count();
    }

    /**
     * Get number of unique url in issues
     *
     * @return int
     */
    public function uniqueUrlIssue()
    {
        return $this->issues()->error()->distinct()->select(['url', 'scope_id'])->get()->count();
    }

    /**
     * Get number of unique url in jobInspects
     *
     * @return int
     */
    public function uniqueUrlJob()
    {
        return $this->jobInspects()->distinct()->select(['url_id', 'scope_id'])->get()->count();
    }
}
