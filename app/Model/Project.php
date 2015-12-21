<?php

namespace Suitcoda\Model;

use Carbon\Carbon;
use Cviebrock\EloquentSluggable\SluggableInterface;
use Cviebrock\EloquentSluggable\SluggableTrait;
use Suitcoda\Model\Inspection;
use Suitcoda\Model\Url;
use Suitcoda\Model\User;

class Project extends BaseModel implements SluggableInterface
{
    use SluggableTrait;

    protected $table = 'projects';

    protected $urlKey = 'slug';

    protected $fillable = [
        'name',
        'slug',
        'main_url',
        'is_crawlable'
    ];

    protected $sluggable = [
        'build_from' => 'name',
        'save_to' => 'slug'
    ];

    /**
     * Get the user for the current project.
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the inspections for the current project.
     *
     * @return object
     */
    public function inspections()
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Get the urls for the current project.
     *
     * @return object
     */
    public function urls()
    {
        return $this->hasMany(Url::class);
    }

    /**
     * Get main url with http prefix
     *
     * @param  string $value []
     * @return string
     */
    public function getMainUrlAttribute($value)
    {
        if (!isset($value)) {
            $urlPrefix = 'http://';
            return url(sprintf('%s', $urlPrefix));
        }
        return $value;
    }

    /**
     * Get updated_at variable with the given format
     *
     * @param  string $value []
     * @return string
     */
    public function getUpdatedAtAttribute($value)
    {
        $time = new Carbon($value);
        return $time->diffForHumans();
    }

    /**
     * Get first inspection object in desc order
     *
     * @return object
     */
    public function getLastInspection()
    {
        return $this->inspections()->latest()->first();
    }

    /**
     * Get related last inspection score
     *
     * @return string
     */
    public function getLastInspectionScoreAttribute()
    {
        if ($this->getLastInspection()) {
            return $this->getLastInspection()->score;
        }
        return '-';
    }

    /**
     * Get related last inspection number
     *
     * @return string
     */
    public function getLastInspectionNumberAttribute()
    {
        if ($this->getLastInspection()) {
            return '#' . $this->getLastInspection()->sequence_number;
        }
        return '-';
    }

    /**
     * Get related last inspection status
     *
     * @return string
     */
    public function getLastInspectionStatusAttribute()
    {
        if ($this->getLastInspection()) {
            return '<b class="text-' . $this->getLastInspection()->statusTextColor . '">' .
                $this->getLastInspection()->statusName . '</b>';
        }
        return '-';
    }

    /**
     * Get related last  completed inspection score
     *
     * @return string
     */
    public function getLastCompletedInspectionScoreAttribute()
    {
        if ($this->inspections()->latestCompleted()->first()) {
            return $this->inspections()->latestCompleted()->first()->score;
        }
        return '-';
    }

    /**
     * Get related last  completed inspection Issues
     *
     * @return string
     */
    public function getLastCompletedInspectionIssuesAttribute()
    {
        if ($this->inspections()->latestCompleted()->first()) {
            return $this->inspections()->latestCompleted()->first()->issues;
        }
        return '-';
    }

    /**
     * Get related last inspection score by category
     *
     * @param string $name []
     * @return string
     */
    public function getLastCompletedInspectionScoreByCategory($name)
    {
        $inspection = $this->inspections()->latestCompleted()->first();
        if ($inspection) {
            if ($inspection->scores()->byCategoryName($name)->first()) {
                return $inspection->scores()->byCategoryName($name)->first()->score;
            }
        }
        return '-';
    }

    /**
     * Get related last inspection issue by category
     *
     * @param string $name []
     * @return string
     */
    public function getLastCompletedInspectionIssueByCategory($name)
    {
        $inspection = $this->inspections()->latestCompleted()->first();
        if ($inspection) {
            if (!$inspection->issues()->byCategoryName($name)->get()->isEmpty()) {
                return $inspection->issues()->byCategoryName($name)->get()->count();
            }
        }
        return '-';
    }

    /**
     * Scope to search project by name
     *
     * @param  string $query   []
     * @param  string $keyword []
     * @return object
     */
    public function scopeSearch($query, $keyword)
    {
        $keywords = explode(' ', $keyword);
        $regex = '';

        if (\DB::connection()->getName() == 'mysql') {
            $regex = 'REGEXP';
        } elseif (\DB::connection()->getName() == 'pgsql') {
            $regex = '~';
        }

        $result = $query->where(function ($q) use ($keywords, $regex) {
            foreach ($keywords as $key) {
                $q->orWhere('name', $regex, "[[:<:]]{$key}[[:>:]]");
            }
        });

        return $result;
    }
}
