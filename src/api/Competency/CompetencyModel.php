<?php

namespace Competencies\Competency;

use Competencies\Course\CourseMapper;
use Spot\Entity\Collection;
use Spot\Locator;

class CompetencyModel
{
    /**
     * @var Locator|null $locator
     */
    private $locator;
    private $courseModel;

    /**
     * @param Locator|null $locator
     * @param CourseMapper $courseModel
     * @return CompetencyModel
     * @internal param null|string $email
     */
    public static function make($locator = null, CourseMapper $courseModel = null) {
        $instance = new self();
        $instance->setLocator($locator);
        $instance->setCourseModel($courseModel);

        return $instance;
    }

    /**
     * @return mixed
     */
    public function getLocator() {
        return $this->locator;
    }

    /**
     * @param mixed $locator
     */
    public function setLocator($locator) {
        $this->locator = $locator;
    }

    /**
     * @param string $entityClass
     * @return \Spot\Mapper
     */
    public function getMapper($entityClass = CompetencyEntity::class) {
        return $this->locator->mapper($entityClass);
    }

    /**
     * @return CourseMapper|null
     */
    public function getCourseModel() {
        return $this->courseModel;
    }

    /**
     * @param CourseMapper $courseModel
     */
    public function setCourseModel($courseModel) {
        $this->courseModel = $courseModel;
    }

    /**
     * @param string $code
     * @return CompetencyEntity|false
     */
    public function load($code) {
        $mapper = $this->getMapper();

        return $mapper->first(['code' => $code]);
    }

    /**
     * @param array $codes
     * @return Collection
     */
    public function loadMultiple($codes) {
        $mapper = $this->getMapper();

        return $mapper->where(['code' => $codes])->execute();
    }

    public function loadProfessions() {
        $groupIndexes = [];
        $professionIndexes = [];

        /**
         * @var CompetencyMapper $mapper
         */
        $mapper = $this->getMapper(CompetencyEntity::class);
        $competencyStats = $mapper->getCompetencyStats();

        $professions = [];

        $competencies = $mapper->all()->with(['professions', 'group']);

        /**
         * @var CompetencyEntity $competencyEntity
         */
        foreach ($competencies as $competencyEntity) {
            /** @var CompetencyGroupEntity $groupEntity */
            $groupEntity = $competencyEntity->relation('group');
            $groupCode = $groupEntity->get('code');

            $skills = [];
            foreach ($competencyEntity->relation('skills') as $skillEntity) {
                $skills[] = $skillEntity->toArray();
            }

            /**
             * @var ProfessionEntity $professionEntity
             */
            foreach ($competencyEntity->relation('professions') as $professionEntity) {
                $professionCode = $professionEntity->get('code');

                if (!isset($professionIndexes[$professionCode])) {
                    $professionIndex = count($professionIndexes);
                    $professionIndexes[$professionCode] = $professionIndex;

                    $professions[$professionIndex] = $professionEntity->toArray();
                    $professions[$professionIndex]['competencyCount'] = 0;
                    $professions[$professionIndex]['courseCount'] = $this->getCourseModel()
                                                        ->countCoursesForProfession($professionCode);
                }
                else {
                    $professionIndex = $professionIndexes[$professionCode];
                }

                if (!isset($groupIndexes[$professionIndex])) {
                    $groupIndexes[$professionIndex] = [];
                }

                if (!isset($groupIndexes[$professionIndex][$groupCode])) {
                    $groupIndex = count($groupIndexes[$professionIndex]);
                    $groupIndexes[$professionIndex][$groupCode] = $groupIndex;
                }
                else {
                    $groupIndex = $groupIndexes[$professionIndex][$groupCode];
                }

                if ( !isset($professions[$professionIndex]['groups'][$groupIndex]) ) {
                    $professions[$professionIndex]['groups'][$groupIndex] = $groupEntity->toArray();
                }

                $competencyData = $competencyEntity->toArray();
                $competencyData['skills'] = $skills;
                $competencyData['average'] = $competencyStats[ $competencyEntity->get('id') ];

                $professions[$professionIndex]['groups'][$groupIndex]['competencies'][] =
                    $competencyData;
                $professions[$professionIndex]['competencyCount']++;
            }

        }

        return $professions;
    }
}