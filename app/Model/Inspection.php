<?php

namespace Suitcoda\Model;

use Carbon\Carbon;
use Suitcoda\Model\JobInspect;
use Suitcoda\Model\Project;
use Suitcoda\Model\Score;
use Suitcoda\Model\SubScope;

class Inspection extends BaseModel
{
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
     * Get score attribute with format
     *
     * @return string
     */
    public function getScoreAttribute()
    {
        if ($this->attributes['score']) {
            return $this->attributes['score'];
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
        if ($this->attributes['status'] == 0) {
            return 'Waiting';
        }
        if ($this->attributes['status'] == 1) {
            return 'On Progress';
        }
        if ($this->attributes['status'] == 2) {
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
        if ($this->attributes['status'] == 0) {
            return 'grey';
        }
        if ($this->attributes['status'] == 1) {
            return 'orange';
        }
        if ($this->attributes['status'] == 2) {
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
        return array_unique($scopeList);
    }

    /**
     * Get related last inspection score by category
     *
     * @param string $name []
     * @return string
     */
    public function getScoreByCategory($name)
    {
        $scoreByCategory = $this->scores()->byCategoryName($name)->first();

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
        if ($this->attributes['status'] == 2) {
            $result = 0;
            foreach ($this->jobInspects()->completed()->get() as $job) {
                $result += $job->issue_count;
            }
            return $result;
        }
        return '(-)';
    }

    public function scopeBySequenceNumber($query, $number)
    {
        return $query->where('sequence_number', $number);
    }

    public function getIssueListByCategory($category)
    {
        $issueList = [];
        dd($this->jobInspects()->completed()->byCategoryName($category)->get());
        foreach ($this->jobInspects()->completed()->byCategoryName($category)->get() as $job) {
            foreach ($job->issues()->get() as $issue) {
                array_push($issueList, $issue);
            }
        }
        return $issueList;
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

    public function scopeLatestCompleted($query)
    {
        $query->where('status', 2)->latest();
    }
}
