<?php

declare(strict_types=1);

namespace Tests\Feature\Contracts;

use App\Models\User;
use cebe\openapi\Reader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\UsesDemoSchool;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\ApiRoute;
use Tests\Support\OpenApiSpec;
use Tests\TestCase;

/**
 * openapi.yaml is the API v2 contract (Bible 3.3 and 5.5). This suite fails
 * when a v2 route has no operation in the spec (or the spec describes a route
 * that does not exist), when a v2 route has no scenario here, and when a v2
 * response does not match the spec: every request below goes through
 * ValidatesOpenApiResponses.
 *
 * Adding a v2 endpoint: describe it in openapi.yaml, add its scenarios to
 * scenarios() (each profile that may call it, and the refusals), and
 * regenerate packages/eschool_api (tool/generate.sh).
 */
class OpenApiContractTest extends TestCase
{
    use UsesDemoSchool;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
    }

    /**
     * Calls whose responses are checked against the spec: name => [who,
     * method, path, body, expected status]. "who" is a DemoSchool person, or
     * superadmin, staff (a role the school created), norole, or null for no
     * token.
     *
     * @return array<string, array{0: ?string, 1: string, 2: string, 3: array<string, mixed>, 4: int}>
     */
    public static function scenarios(): array
    {
        return [
            'getRecognitionAccess requires auth' => [null, 'GET', 'api/v2/recognition/house_points/access', [], 401],
            'getRecognitionConfiguration requires auth' => [null, 'GET', 'api/v2/recognition/house_points/configuration', [], 401],
            'getRecognitionLearners requires auth' => [null, 'GET', 'api/v2/recognition/house_points/learners', [], 401],
            'getHouseCounters requires auth' => [null, 'GET', 'api/v2/recognition/house_points/counters', [], 401],
            'getHousePointHistory requires auth' => [null, 'GET', 'api/v2/recognition/house_points/learners/1/points', [], 401],
            'getPermanentAwards requires auth' => [null, 'GET', 'api/v2/recognition/house_points/learners/1/awards', [], 401],
            'getAwardNominationCatalogue requires auth' => [null, 'GET', 'api/v2/recognition/house_points/nomination-catalogue', [], 401],
            'getAwardNominations requires auth' => [null, 'GET', 'api/v2/recognition/house_points/nominations', [], 401],
            'nominateAward requires auth' => [null, 'POST', 'api/v2/recognition/house_points/nominations', [], 401],
            'configureRecognition requires auth' => [null, 'POST', 'api/v2/recognition/house_points/settings', [], 401],
            'createRecognitionCatalogue requires auth' => [null, 'POST', 'api/v2/recognition/house_points/catalogue/houses', [], 401],
            'saveRecognitionCatalogue requires auth' => [null, 'POST', 'api/v2/recognition/house_points/catalogue/houses/1', [], 401],
            'issueHousePoints requires auth' => [null, 'POST', 'api/v2/recognition/house_points/points', [], 401],
            'reverseHousePoints requires auth' => [null, 'POST', 'api/v2/recognition/house_points/points/1/reverse', [], 401],
            'reviewAwardNomination requires auth' => [null, 'POST', 'api/v2/recognition/house_points/nominations/1/review', [], 401],
            'withdrawAwardNomination requires auth' => [null, 'POST', 'api/v2/recognition/house_points/nominations/1/withdraw', [], 401],
            'presentAward requires auth' => [null, 'POST', 'api/v2/recognition/house_points/presentations', [], 401],
            'recordOutsideAward requires auth' => [null, 'POST', 'api/v2/recognition/house_points/outside-awards', [], 401],
            'voidAward requires auth' => [null, 'POST', 'api/v2/recognition/house_points/presentations/1/void', [], 401],
            'getTransferAccess requires auth' => [null, 'GET', 'api/v2/transfers/access', [], 401],
            'getTransferChoices requires auth' => [null, 'GET', 'api/v2/transfers/choices/learners', [], 401],
            'prepareTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages', [], 401],
            'getTransfers requires auth' => [null, 'GET', 'api/v2/transfers/outgoing', [], 401],
            'getTransfer requires auth' => [null, 'GET', 'api/v2/transfers/outgoing/1', [], 401],
            'downloadTransferArchive requires auth' => [null, 'GET', 'api/v2/transfers/outgoing/1/archive', [], 401],
            'consentTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages/1/consent', [], 401],
            'withdrawTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages/1/withdraw', [], 401],
            'releaseTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages/1/release', [], 401],
            'cancelTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages/1/cancel', [], 401],
            'acceptTransfer requires auth' => [null, 'POST', 'api/v2/transfers/packages/1/accept', [], 401],
            'getPublicIncidentConfiguration route boundary' => [null, 'GET', 'api/v2/wellbeing/public/unknown-school/configuration', [], 404],
            'submitAnonymousIncident route boundary' => [null, 'POST', 'api/v2/wellbeing/public/unknown-school/reports', [], 422],
            'getAnonymousIncidentFollowUp route boundary' => [null, 'POST', 'api/v2/wellbeing/public/unknown-school/follow-up', [], 422],
            'replyAnonymousIncident route boundary' => [null, 'POST', 'api/v2/wellbeing/public/unknown-school/follow-up/replies', [], 422],
            'getWellbeingAccess route boundary' => [null, 'GET', 'api/v2/wellbeing/access', [], 401],
            'getWellbeingConfiguration route boundary' => [null, 'GET', 'api/v2/wellbeing/configuration', [], 401],
            'listWellbeingManagedCases route boundary' => [null, 'GET', 'api/v2/wellbeing/management/cases', [], 401],
            'listWellbeingTeamAssignments route boundary' => [null, 'GET', 'api/v2/wellbeing/categories/1/team', [], 401],
            'listWellbeingCaseAssignments route boundary' => [null, 'GET', 'api/v2/wellbeing/management/cases/1/assignments', [], 401],
            'listWellbeingReports route boundary' => [null, 'GET', 'api/v2/wellbeing/reports', [], 401],
            'getWellbeingReport route boundary' => [null, 'GET', 'api/v2/wellbeing/reports/1', [], 401],
            'listWellbeingCases route boundary' => [null, 'GET', 'api/v2/wellbeing/cases', [], 401],
            'listWellbeingCaseProfessionals route boundary' => [null, 'GET', 'api/v2/wellbeing/cases/1/professionals', [], 401],
            'listWellbeingCaseIncidentChoices route boundary' => [null, 'GET', 'api/v2/wellbeing/cases/1/incident-choices', [], 401],
            'getWellbeingCase route boundary' => [null, 'GET', 'api/v2/wellbeing/cases/1', [], 401],
            'listWellbeingAppointments route boundary' => [null, 'GET', 'api/v2/wellbeing/cases/1/appointments', [], 401],
            'createWellbeingCategory route boundary' => [null, 'POST', 'api/v2/wellbeing/categories', [], 401],
            'saveWellbeingCategory route boundary' => [null, 'POST', 'api/v2/wellbeing/categories/1', [], 401],
            'createWellbeingTeamAssignment route boundary' => [null, 'POST', 'api/v2/wellbeing/categories/1/team', [], 401],
            'saveWellbeingTeamAssignment route boundary' => [null, 'POST', 'api/v2/wellbeing/categories/1/team/1', [], 401],
            'openWellbeingCase route boundary' => [null, 'POST', 'api/v2/wellbeing/management/cases', [], 401],
            'createWellbeingCaseAssignment route boundary' => [null, 'POST', 'api/v2/wellbeing/management/cases/1/assignments', [], 401],
            'saveWellbeingCaseAssignment route boundary' => [null, 'POST', 'api/v2/wellbeing/management/cases/1/assignments/1', [], 401],
            'submitIdentifiedIncident route boundary' => [null, 'POST', 'api/v2/wellbeing/own/reports', [], 401],
            'getIdentifiedIncidentFollowUp route boundary' => [null, 'POST', 'api/v2/wellbeing/own/follow-up', [], 401],
            'replyIdentifiedIncident route boundary' => [null, 'POST', 'api/v2/wellbeing/own/follow-up/replies', [], 401],
            'identifyIncidentReporter route boundary' => [null, 'POST', 'api/v2/wellbeing/own/identify', [], 401],
            'replyWellbeingReport route boundary' => [null, 'POST', 'api/v2/wellbeing/reports/1/replies', [], 401],
            'addWellbeingCaseEntry route boundary' => [null, 'POST', 'api/v2/wellbeing/cases/1/entries', [], 401],
            'linkWellbeingCaseIncident route boundary' => [null, 'POST', 'api/v2/wellbeing/cases/1/incident-link', [], 401],
            'setWellbeingCaseStatus route boundary' => [null, 'POST', 'api/v2/wellbeing/cases/1/status', [], 401],
            'createWellbeingAppointment route boundary' => [null, 'POST', 'api/v2/wellbeing/cases/1/appointments', [], 401],
            'saveWellbeingAppointment route boundary' => [null, 'POST', 'api/v2/wellbeing/cases/1/appointments/1', [], 401],
            'facilitator sign-in validation' => [null, 'POST', 'api/v2/facilitator/sign-in', [], 422],
            'facilitator session requires auth' => [null, 'GET', 'api/v2/facilitator/session', [], 401],
            'facilitator sign-out requires auth' => [null, 'POST', 'api/v2/facilitator/sign-out', [], 401],
            'getSupportAccess requires auth' => [null, 'GET', 'api/v2/pastoral/support/access', [], 401],
            'getSupportConfiguration requires auth' => [null, 'GET', 'api/v2/pastoral/support/configuration/professional', [], 401],
            'listSupportCases requires auth' => [null, 'GET', 'api/v2/pastoral/support/cases', [], 401],
            'getSupportPlan requires auth' => [null, 'GET', 'api/v2/pastoral/support/plans/1', [], 401],
            'updateSupportPlan requires auth' => [null, 'PUT', 'api/v2/pastoral/support/plans/1', [], 401],
            'listSupportReviews requires auth' => [null, 'GET', 'api/v2/pastoral/support/plans/1/reviews', [], 401],
            'reviewSupportPlan requires auth' => [null, 'POST', 'api/v2/pastoral/support/plans/1/reviews', [], 401],
            'listSupportVersions requires auth' => [null, 'GET', 'api/v2/pastoral/support/plans/1/versions', [], 401],
            'listSupportDisclosures requires auth' => [null, 'GET', 'api/v2/pastoral/support/plans/1/disclosures', [], 401],
            'discloseSupportInstructions requires auth' => [null, 'POST', 'api/v2/pastoral/support/plans/1/disclosures', [], 401],
            'listSupportAssignments requires auth' => [null, 'GET', 'api/v2/pastoral/support/assignments', [], 401],
            'createSupportAssignment requires auth' => [null, 'POST', 'api/v2/pastoral/support/assignments', [], 401],
            'listMySupportInstructions requires auth' => [null, 'GET', 'api/v2/pastoral/support/teacher', [], 401],
            'getMySupportInstructions requires auth' => [null, 'GET', 'api/v2/pastoral/support/teacher/1', [], 401],
            'createSupportPlan requires auth' => [null, 'POST', 'api/v2/pastoral/support/plans', [], 401],
            'closeSupportPlan requires auth' => [null, 'POST', 'api/v2/pastoral/support/plans/1/close', [], 401],
            'revokeSupportAssignment requires auth' => [null, 'POST', 'api/v2/pastoral/support/assignments/1/revoke', [], 401],
            'revokeSupportInstructions requires auth' => [null, 'POST', 'api/v2/pastoral/support/disclosures/1/revoke', [], 401],
            'getCoverConfiguration requires auth' => [null, 'GET', 'api/v2/pastoral/cover/configuration', [], 401],
            'getMyCover requires auth' => [null, 'GET', 'api/v2/pastoral/cover/mine', [], 401],
            'createCoverAbsence requires auth' => [null, 'POST', 'api/v2/pastoral/cover/absences', [], 401],
            'getCoverAbsence requires auth' => [null, 'GET', 'api/v2/pastoral/cover/absences/1', [], 401],
            'refreshCoverAbsence requires auth' => [null, 'POST', 'api/v2/pastoral/cover/absences/1/refresh', [], 401],
            'cancelCoverAbsence requires auth' => [null, 'POST', 'api/v2/pastoral/cover/absences/1/cancel', [], 401],
            'getCoverOccurrence requires auth' => [null, 'GET', 'api/v2/pastoral/cover/occurrences/1', [], 401],
            'getCoverCandidates requires auth' => [null, 'GET', 'api/v2/pastoral/cover/occurrences/1/candidates', [], 401],
            'assignCoverOccurrence requires auth' => [null, 'PUT', 'api/v2/pastoral/cover/occurrences/1/assignment', [], 401],
            'listActivityGroups module-off' => ['superadmin', 'GET', 'api/v2/activities/groups', [], 404],
            'createActivityGroup module-off' => ['superadmin', 'POST', 'api/v2/activities/groups', [], 404],
            'readActivityGroup module-off' => ['superadmin', 'GET', 'api/v2/activities/groups/999999', [], 404],
            'saveActivityGroup module-off' => ['superadmin', 'PUT', 'api/v2/activities/groups/999999', [], 404],
            'readActivityGroupRoster module-off' => ['superadmin', 'GET', 'api/v2/activities/groups/999999/roster', [], 404],
            'createActivityMembership module-off' => ['superadmin', 'POST', 'api/v2/activities/groups/999999/memberships', [], 404],
            'saveActivityMembership module-off' => ['superadmin', 'PUT', 'api/v2/activities/groups/999999/memberships/999999', [], 404],
            'createActivityStaffAllocation module-off' => ['superadmin', 'POST', 'api/v2/activities/groups/999999/staff', [], 404],
            'saveActivityStaffAllocation module-off' => ['superadmin', 'PUT', 'api/v2/activities/groups/999999/staff/999999', [], 404],
            'createActivitySession module-off' => ['superadmin', 'POST', 'api/v2/activities/groups/999999/sessions', [], 404],
            'saveActivitySession module-off' => ['superadmin', 'PUT', 'api/v2/activities/groups/999999/sessions/999999', [], 404],
            'createActivityMaterial module-off' => ['superadmin', 'POST', 'api/v2/activities/groups/999999/materials', [], 404],
            'saveActivityMaterial module-off' => ['superadmin', 'PUT', 'api/v2/activities/groups/999999/materials/999999', [], 404],
            'captureActivitySessionAttendance module-off' => ['superadmin', 'PUT', 'api/v2/activities/sessions/999999/attendance', [], 404],
            'getCommunicationConfiguration' => ['superadmin', 'GET', 'api/v2/communication/configuration', [], 200],
            'listCommunicationContacts' => ['superadmin', 'GET', 'api/v2/communication/contacts', ['session_year_id' => 1], 200],
            'listCommunicationAudiences' => ['superadmin', 'GET', 'api/v2/communication/audiences', ['session_year_id' => 1], 200],
            'createCommunicationAudience' => ['superadmin', 'POST', 'api/v2/communication/audiences', [], 422],
            'saveCommunicationAudience' => ['superadmin', 'PUT', 'api/v2/communication/audiences/999999', [], 404],
            'listCommunicationRoleRules' => ['superadmin', 'GET', 'api/v2/communication/rules', [], 200],
            'saveCommunicationRoleRule' => ['superadmin', 'PUT', 'api/v2/communication/rules/999999', [], 422],
            'listSchoolMessageDrafts' => ['superadmin', 'GET', 'api/v2/communication/drafts', [], 200],
            'createSchoolMessageDraft' => ['superadmin', 'POST', 'api/v2/communication/drafts', [], 422],
            'getSchoolMessageEditor' => ['superadmin', 'GET', 'api/v2/communication/drafts/999999', [], 404],
            'saveSchoolMessageDraft' => ['superadmin', 'PUT', 'api/v2/communication/drafts/999999', [], 404],
            'previewSchoolMessage' => ['superadmin', 'GET', 'api/v2/communication/drafts/999999/preview', [], 404],
            'submitSchoolMessage' => ['superadmin', 'POST', 'api/v2/communication/drafts/999999/submit', [], 404],
            'decideSchoolMessage' => ['superadmin', 'POST', 'api/v2/communication/drafts/999999/decision', [], 404],
            'withdrawSchoolMessage' => ['superadmin', 'POST', 'api/v2/communication/drafts/999999/withdraw', [], 404],
            'listSchoolMessageInbox' => ['farid', 'GET', 'api/v2/communication/messages', [], 200],
            'readSchoolMessage' => ['farid', 'GET', 'api/v2/communication/messages/999999', [], 404],
            'markSchoolMessageRead' => ['farid', 'POST', 'api/v2/communication/messages/999999/read', [], 404],
            'communication family cannot author' => ['farid', 'POST', 'api/v2/communication/drafts', [], 403],
            'communication teacher cannot set rules' => ['tara', 'GET', 'api/v2/communication/rules', [], 403],
            'communication unauthenticated inbox' => [null, 'GET', 'api/v2/communication/messages', [], 401],
            'getAcademicContentConfiguration' => ['superadmin', 'GET', 'api/v2/academics/content/configuration', [], 200],
            'listAcademicContent' => ['superadmin', 'GET', 'api/v2/academics/content/items', [], 200],
            'uploadAcademicContent' => ['superadmin', 'POST', 'api/v2/academics/content/items', [], 422],
            'getAcademicContent' => ['superadmin', 'GET', 'api/v2/academics/content/items/999999', [], 404],
            'replaceAcademicContentSource' => ['superadmin', 'POST', 'api/v2/academics/content/items/999999/source', [], 404],
            'archiveAcademicContent' => ['superadmin', 'POST', 'api/v2/academics/content/items/999999/archive', [], 404],
            'saveAcademicContentSettings' => ['superadmin', 'PUT', 'api/v2/academics/content/settings', [], 422],
            'getAcademicContentVersion' => ['superadmin', 'GET', 'api/v2/academics/content/versions/999999', [], 404],
            'saveAcademicContentDraft' => ['superadmin', 'PUT', 'api/v2/academics/content/versions/999999', [], 404],
            'previewAcademicContentPublication' => ['superadmin', 'GET', 'api/v2/academics/content/versions/999999/preview', [], 404],
            'retryAcademicContentConversion' => ['superadmin', 'POST', 'api/v2/academics/content/versions/999999/retry', [], 404],
            'publishAcademicContent' => ['superadmin', 'POST', 'api/v2/academics/content/versions/999999/publish', [], 404],
            'listLearnerContent' => ['farid', 'GET', 'api/v2/academics/content/learners/1', [], 200],
            'revalidateSavedLearnerContent' => ['farid', 'POST', 'api/v2/academics/content/learners/1/saved-access', ['version_ids' => [999999]], 200],
            'readLearnerContent' => ['farid', 'GET', 'api/v2/academics/content/learners/1/versions/999999', [], 404],
            'markLearnerContentRead' => ['farid', 'POST', 'api/v2/academics/content/learners/1/versions/999999/read', [], 404],
            'getAcademicTransitionConfiguration' => ['superadmin', 'GET', 'api/v2/academics/transitions/configuration', [], 200],
            'searchAcademicTransitionLearners' => ['superadmin', 'GET', 'api/v2/academics/transitions/learners', [], 200],
            'getAcademicTransitionLearner' => ['superadmin', 'GET', 'api/v2/academics/transitions/learners/999999', [], 404],
            'saveAcademicTransitionGradePolicy' => ['superadmin', 'PUT', 'api/v2/academics/transitions/policies/999999', [], 422],
            'saveAcademicExitRetention' => ['superadmin', 'PUT', 'api/v2/academics/transitions/retention', [], 422],
            'previewAcademicTransition' => ['superadmin', 'POST', 'api/v2/academics/transitions/preview', [], 422],
            'commitAcademicTransition' => ['superadmin', 'POST', 'api/v2/academics/transitions/commit', [], 422],
            'previewAcademicExitPackage' => ['superadmin', 'POST', 'api/v2/academics/transitions/exports/preview', [], 422],
            'prepareAcademicExitPackage' => ['superadmin', 'POST', 'api/v2/academics/transitions/exports/prepare', [], 422],
            'getAcademicExitPackage' => ['superadmin', 'GET', 'api/v2/academics/transitions/exports/999999', [], 404],
            'retryAcademicExitPackage' => ['superadmin', 'POST', 'api/v2/academics/transitions/exports/999999/retry', [], 404],
            'downloadAcademicExitPackage' => ['superadmin', 'GET', 'api/v2/academics/transitions/exports/999999/download', [], 403],
            'transition config parent refusal' => ['farid', 'GET', 'api/v2/academics/transitions/configuration', [], 403],
            'report link refresh unknown card' => ['farid', 'GET', 'api/v2/academics/reports/cards/999999/access', [], 404],
            'report roster validation' => ['superadmin', 'GET', 'api/v2/academics/reports/roster', [], 422],
            'report config parent refusal' => ['farid', 'GET', 'api/v2/academics/reports/configuration', [], 403],
            'report config staff' => ['superadmin', 'GET', 'api/v2/academics/reports/configuration', [], 200],
            'report templates validation' => ['superadmin', 'PUT', 'api/v2/academics/reports/templates', [], 422],
            'report banks validation' => ['superadmin', 'PUT', 'api/v2/academics/reports/comment-banks', [], 422],
            'report preview validation' => ['superadmin', 'POST', 'api/v2/academics/reports/preview', [], 422],
            'report prepare validation' => ['superadmin', 'POST', 'api/v2/academics/reports/prepare', [], 422],
            'report comment list teacher' => ['tara', 'GET', 'api/v2/academics/reports/comments', ['session_year_id' => 1], 200],
            'report family own child' => ['farid', 'GET', 'api/v2/academics/reports/learners/1', [], 200],
            'report comment unknown card' => ['tara', 'POST', 'api/v2/academics/reports/cards/999999/comment', [], 404],
            'report read unknown card' => ['farid', 'POST', 'api/v2/academics/reports/cards/999999/read', [], 404],
            'report download requires signature' => ['farid', 'GET', 'api/v2/academics/reports/cards/999999/download', [], 403],
            'report batches staff' => ['superadmin', 'GET', 'api/v2/academics/reports/batches', ['session_year_id' => 1], 200],
            'report batch unknown' => ['superadmin', 'GET', 'api/v2/academics/reports/batches/999999', [], 404],
            'report source-preview unknown' => ['superadmin', 'GET', 'api/v2/academics/reports/batches/999999/source-preview', [], 404],
            'report release-preview unknown' => ['superadmin', 'GET', 'api/v2/academics/reports/batches/999999/release-preview', [], 404],
            'report refresh unknown' => ['superadmin', 'POST', 'api/v2/academics/reports/batches/999999/refresh', [], 404],
            'report grade-sign unknown' => ['superadmin', 'POST', 'api/v2/academics/reports/batches/999999/grade-sign', [], 404],
            'report principal-sign unknown' => ['superadmin', 'POST', 'api/v2/academics/reports/batches/999999/principal-sign', [], 404],
            'report release unknown' => ['superadmin', 'POST', 'api/v2/academics/reports/batches/999999/release', [], 404],
            'report retry unknown' => ['superadmin', 'POST', 'api/v2/academics/reports/batches/999999/retry', [], 404],
            'register choices unauthenticated' => [null, 'GET', 'api/v2/academics/registers/choices', [], 401],
            'register choices parent denied' => ['farid', 'GET', 'api/v2/academics/registers/choices', [], 403],
            'register open validation' => ['superadmin', 'POST', 'api/v2/academics/registers/open', [], 422],
            'register show unknown' => ['superadmin', 'GET', 'api/v2/academics/registers/999999', [], 404],
            'register sync unknown' => ['superadmin', 'POST', 'api/v2/academics/registers/999999/sync', [], 404],
            'register settings read' => ['superadmin', 'GET', 'api/v2/academics/registers/settings', [], 200],
            'register settings validation' => ['superadmin', 'PUT', 'api/v2/academics/registers/settings', [], 422],
            'register today own learner' => ['asha', 'GET', 'api/v2/academics/registers/learners/1/today', [], 200],
            'register qr rotate unknown learner' => ['farid', 'POST', 'api/v2/academics/registers/learners/999999/qr/rotate', [], 404],
            'register qr unknown learner' => ['farid', 'GET', 'api/v2/academics/registers/learners/999999/qr', [], 404],
            'timetable config auth' => [null, 'GET', 'api/v2/academics/timetable/configuration', [], 401],
            'timetable config admin' => ['superadmin', 'GET', 'api/v2/academics/timetable/configuration', ['session_year_id' => 1], 200],
            'timetable week teacher' => ['tara', 'GET', 'api/v2/academics/timetable/week', ['session_year_id' => 1, 'starts_on' => '2026-02-09'], 200],
            'timetable next learner' => ['asha', 'GET', 'api/v2/academics/timetable/next', [], 200],
            'timetable room create validation' => ['superadmin', 'POST', 'api/v2/academics/timetable/rooms', [], 422],
            'timetable room update validation' => ['superadmin', 'PUT', 'api/v2/academics/timetable/rooms/999999', [], 422],
            'timetable bell create validation' => ['superadmin', 'POST', 'api/v2/academics/timetable/bells', [], 422],
            'timetable bell update validation' => ['superadmin', 'PUT', 'api/v2/academics/timetable/bells/999999', [], 422],
            'timetable preview validation' => ['superadmin', 'POST', 'api/v2/academics/timetable/preview', [], 422],
            'timetable commit validation' => ['superadmin', 'POST', 'api/v2/academics/timetable/commit', [], 422],
            'timetable import validation' => ['superadmin', 'POST', 'api/v2/academics/timetable/import', [], 422],
            'timetable parent management denied' => ['farid', 'POST', 'api/v2/academics/timetable/rooms', [], 403],
            'released mark periods unknown' => ['farid', 'GET', 'api/v2/academics/marks/learners/999999/periods', [], 404],
            'mark matrix requires auth' => [null, 'GET', 'api/v2/academics/marks/groups/999999', [], 401],
            'mark matrix unknown' => ['superadmin', 'GET', 'api/v2/academics/marks/groups/999999', [], 404],
            'mark preview unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/tasks/999999/preview', [], 404],
            'mark capture unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/tasks/999999/capture', [], 404],
            'mark correct unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/tasks/999999/correct', [], 404],
            'mark moderate unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/tasks/999999/moderate', [], 404],
            'mark release preview unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/release-preview', [], 404],
            'mark release unknown' => ['superadmin', 'POST', 'api/v2/academics/marks/groups/999999/release', [], 404],
            'released marks unknown' => ['farid', 'GET', 'api/v2/academics/marks/learners/999999', [], 404],
            'teaching group list teacher' => ['tara', 'GET', 'api/v2/academics/teaching-groups', [], 200],
            'teaching group list parent denied' => ['farid', 'GET', 'api/v2/academics/teaching-groups', [], 403],
            'teaching group list auth' => [null, 'GET', 'api/v2/academics/teaching-groups', [], 401],
            'teaching group create validates' => ['superadmin', 'POST', 'api/v2/academics/teaching-groups', [], 422],
            'teaching group show unknown' => ['superadmin', 'GET', 'api/v2/academics/teaching-groups/999999', [], 404],
            'teaching group update unknown' => ['superadmin', 'PUT', 'api/v2/academics/teaching-groups/999999', [], 404],
            'teaching group rows unknown' => ['superadmin', 'GET', 'api/v2/academics/teaching-groups/999999/memberships', [], 404],
            'teaching group assignment unknown' => ['superadmin', 'POST', 'api/v2/academics/teaching-groups/999999/teachers', [], 404],
            'teaching group row update unknown' => ['superadmin', 'PUT', 'api/v2/academics/teaching-groups/999999/teachers/999999', [], 404],
            'academic allocation list' => ['superadmin', 'GET', 'api/v2/academics/allocations/homerooms', [], 200],
            'academic allocation create validates' => ['superadmin', 'POST', 'api/v2/academics/allocations/leadership', [], 422],
            'academic allocation update validates' => ['superadmin', 'PUT', 'api/v2/academics/allocations/homerooms/999999', [], 422],
            'curriculum plans as admin' => ['superadmin', 'GET', 'api/v2/academics/curriculum-plans', [], 200],
            'curriculum plans as teacher' => ['tara', 'GET', 'api/v2/academics/curriculum-plans', [], 200],
            'curriculum plans parent denied' => ['farid', 'GET', 'api/v2/academics/curriculum-plans', [], 403],
            'curriculum plan requires auth' => [null, 'GET', 'api/v2/academics/curriculum-plans', [], 401],
            'curriculum create validation' => ['superadmin', 'POST', 'api/v2/academics/curriculum-plans', [], 422],
            'curriculum read unknown' => ['superadmin', 'GET', 'api/v2/academics/curriculum-plans/999999', [], 404],
            'curriculum update unknown' => ['superadmin', 'PUT', 'api/v2/academics/curriculum-plans/999999', [], 404],
            'curriculum copy unknown' => ['superadmin', 'POST', 'api/v2/academics/curriculum-plans/999999/copy', ['session_year_id' => 1], 404],
            'curriculum publish unknown' => ['superadmin', 'POST', 'api/v2/academics/curriculum-plans/999999/assessments/999999/publish', ['revision' => 1], 404],
            'curriculum publish teacher denied' => ['tara', 'POST', 'api/v2/academics/curriculum-plans/1/assessments/1/publish', ['revision' => 1], 403],
            'subject catalogue as learner' => ['asha', 'GET', 'api/v2/academics/subject-standards', [], 200],
            'subject catalogue as teacher' => ['tara', 'GET', 'api/v2/academics/subject-standards', [], 200],
            'subject catalogue requires auth' => [null, 'GET', 'api/v2/academics/subject-standards', [], 401],
            'subject standard as admin' => ['superadmin', 'GET', 'api/v2/academics/subjects/1/standard', [], 200],
            'subject standard cleared' => ['superadmin', 'PUT', 'api/v2/academics/subjects/1/standard', ['standard_id' => null], 200],
            'subject standard teacher denied' => ['tara', 'PUT', 'api/v2/academics/subjects/1/standard', ['standard_id' => 1], 403],
            'subject from standard validates input' => ['superadmin', 'POST', 'api/v2/academics/subjects/from-standard', [], 422],
            'subject from standard parent denied' => ['farid', 'POST', 'api/v2/academics/subjects/from-standard', [], 403],
            'school terms as learner' => ['asha', 'GET', 'api/v2/academics/terms', ['session_year_id' => 1], 200],
            'school terms as parent' => ['farid', 'GET', 'api/v2/academics/terms', ['session_year_id' => 1], 200],
            'school terms as teacher' => ['tara', 'GET', 'api/v2/academics/terms', ['session_year_id' => 1], 200],
            'school terms as admin' => ['superadmin', 'GET', 'api/v2/academics/terms', ['session_year_id' => 1], 200],
            'school terms requires auth' => [null, 'GET', 'api/v2/academics/terms', [], 401],
            'school terms validates year' => ['superadmin', 'GET', 'api/v2/academics/terms', [], 422],
            'school term creation validates dates' => ['superadmin', 'POST', 'api/v2/academics/terms', [], 422],
            'school term creation denied' => ['tara', 'POST', 'api/v2/academics/terms', [], 403],
            'school term edit denied' => ['farid', 'PUT', 'api/v2/academics/terms/1', [], 403],
            'school term delete unknown' => ['superadmin', 'DELETE', 'api/v2/academics/terms/999999', [], 404],
            'school term delete denied' => ['asha', 'DELETE', 'api/v2/academics/terms/1', [], 403],
            'calendar templates as admin' => ['superadmin', 'GET', 'api/v2/academics/term-templates', [], 200],
            'calendar templates denied' => ['tara', 'GET', 'api/v2/academics/term-templates', [], 403],
            'calendar template creation validation' => ['superadmin', 'POST', 'api/v2/academics/term-templates', [], 422],
            'calendar template creation denied' => ['farid', 'POST', 'api/v2/academics/term-templates', [], 403],
            'calendar template preview unknown' => ['superadmin', 'POST', 'api/v2/academics/term-templates/999999/apply', ['session_year_id' => 1], 404],
            'calendar template apply denied' => ['asha', 'POST', 'api/v2/academics/term-templates/1/apply', ['session_year_id' => 1, 'apply' => true], 403],
            'academic reference as learner' => ['asha', 'GET', 'api/v2/academics/reference', [], 200],
            'academic reference as parent' => ['farid', 'GET', 'api/v2/academics/reference', [], 200],
            'academic reference as teacher' => ['tara', 'GET', 'api/v2/academics/reference', [], 200],
            'academic reference as admin' => ['superadmin', 'GET', 'api/v2/academics/reference', [], 200],
            'academic reference requires auth' => [null, 'GET', 'api/v2/academics/reference', [], 401],
            'academic reference requires membership' => ['norole', 'GET', 'api/v2/academics/reference', [], 403],
            'class grade as admin' => ['superadmin', 'GET', 'api/v2/academics/classes/1/grade-level', [], 200],
            'class grade mapped' => ['superadmin', 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => 1], 200],
            'class grade cleared' => ['superadmin', 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => null], 200],
            'class grade invalid' => ['superadmin', 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => 999999], 422],
            'class grade unknown' => ['superadmin', 'GET', 'api/v2/academics/classes/999999/grade-level', [], 404],
            'class grade teacher denied' => ['tara', 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => 1], 403],
            'class grade parent denied' => ['farid', 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => 1], 403],
            'class grade learner denied' => ['asha', 'GET', 'api/v2/academics/classes/1/grade-level', [], 403],
            'class grade read requires auth' => [null, 'GET', 'api/v2/academics/classes/1/grade-level', [], 401],
            'class grade write requires auth' => [null, 'PUT', 'api/v2/academics/classes/1/grade-level', ['grade_level_id' => 1], 401],
            'profile file upload requires auth' => [null, 'POST', 'api/v2/me/profile/fields/document/file', [], 401],
            'person file upload requires auth' => [null, 'POST', 'api/v2/people/1/profile/fields/document/file', [], 401],
            'my school as learner' => ['asha', 'GET', 'api/v2/schools/1/my-school', [], 200],
            'my school as guardian' => ['farid', 'GET', 'api/v2/schools/1/my-school', [], 200],
            'my school as teacher' => ['tara', 'GET', 'api/v2/schools/1/my-school', [], 200],
            'my school as admin' => ['superadmin', 'GET', 'api/v2/schools/1/my-school', [], 200],
            'my school requires auth' => [null, 'GET', 'api/v2/schools/1/my-school', [], 401],
            'my school wrong scope' => ['farid', 'GET', 'api/v2/schools/999999/my-school', [], 404],
            'read learner profile' => ['asha', 'GET', 'api/v2/me/profile', [], 200],
            'read guardian profile' => ['farid', 'GET', 'api/v2/me/profile', [], 200],
            'read teacher profile' => ['tara', 'GET', 'api/v2/me/profile', [], 200],
            'profile write acknowledgement' => ['farid', 'PUT', 'api/v2/me/profile', ['values' => ['first_name' => ['text' => 'Farid']]], 200],
            'profile write requires auth' => [null, 'PUT', 'api/v2/me/profile', ['values' => ['first_name' => ['text' => 'No']]], 401],
            'person profile wrong role' => ['gina', 'GET', 'api/v2/people/999999/profile', [], 404],
            'person profile write requires auth' => [null, 'PUT', 'api/v2/people/1/profile', ['values' => ['first_name' => ['text' => 'No']]], 401],
            'profile configuration list' => ['superadmin', 'GET', 'api/v2/profile-fields', [], 200],
            'profile category create' => ['superadmin', 'POST', 'api/v2/profile-fields/categories', ['profile_type' => 'learner', 'key' => 'contract_transport', 'label' => 'Transport'], 200],
            'profile category update' => ['superadmin', 'PUT', 'api/v2/profile-fields/categories/1', ['profile_type' => 'learner', 'key' => 'identity', 'label' => 'Identity', 'sort_order' => 20], 200],
            'profile category removal protected' => ['superadmin', 'DELETE', 'api/v2/profile-fields/categories/1', [], 422],
            'profile field create' => ['superadmin', 'POST', 'api/v2/profile-fields/definitions', ['category_id' => 1, 'key' => 'contract_route', 'label' => 'Route', 'type' => 'text', 'visible_to' => ['owner', 'school_admin'], 'editable_by' => ['school_admin']], 200],
            'profile field update unknown' => ['superadmin', 'PUT', 'api/v2/profile-fields/definitions/999999', ['category_id' => 1, 'key' => 'contract_route', 'label' => 'Route', 'type' => 'text', 'visible_to' => ['owner'], 'editable_by' => []], 404],
            'profile field removal protected' => ['superadmin', 'DELETE', 'api/v2/profile-fields/definitions/1', [], 422],
            'read own notification preferences' => ['farid', 'GET', 'api/v2/me/notification-preferences', [], 200],
            'locked notice preference' => ['farid', 'PUT', 'api/v2/me/notification-preferences', ['type' => 'attendance.absence', 'channel' => 'email', 'enabled' => false], 422],
            'read own handle' => ['asha', 'GET', 'api/v2/me/handle', [], 200],
            'handle write requires auth' => [null, 'PUT', 'api/v2/me/handle', ['handle' => 'orbit'], 401],
            'unverified guardian handle list' => ['farid', 'GET', 'api/v2/guardian/handle-proposals', [], 403],
            'handle decision wrong role' => ['tara', 'POST', 'api/v2/guardian/handle-proposals/1', ['decision' => 'approve'], 403],
            'enrolment list wrong role' => ['farid', 'GET', 'api/v2/staff/learners/1/enrolments', [], 403],
            'enrolment write wrong role' => ['farid', 'POST', 'api/v2/staff/learners/1/enrolments', [], 403],
            'unknown invitation' => [null, 'GET', 'api/v2/guardian/invitations/1', [], 404],
            'unknown invitation accept' => [null, 'POST', 'api/v2/guardian/invitations/1/accept', [], 404],
            'invitation issue wrong role' => ['farid', 'POST', 'api/v2/guardians/1/invitations', [], 403],
            'guardian approvals list' => ['farid', 'GET', 'api/v2/guardian/approvals', [], 200],
            'guardian decision wrong role' => ['tara', 'POST', 'api/v2/guardian/approvals/1', ['decision' => 'approve', 'consent_version' => '2026-10-23'], 403],
            'context as a learner' => ['asha', 'GET', 'api/v2/me/context', [], 200],
            'context as a parent of two' => ['farid', 'GET', 'api/v2/me/context', [], 200],
            'context as a guardian' => ['gina', 'GET', 'api/v2/me/context', [], 200],
            'context as a teacher' => ['tara', 'GET', 'api/v2/me/context', [], 200],
            'context as the super admin' => ['superadmin', 'GET', 'api/v2/me/context', [], 200],
            'context as school-created staff' => ['staff', 'GET', 'api/v2/me/context', [], 200],
            'getInterventionOwnerChoices requires auth' => [null, 'GET', 'api/v2/growth/risk/learners/1/owners', [], 401],
            'getRiskConfiguration requires auth' => [null, 'GET', 'api/v2/growth/risk/configuration', [], 401],
            'saveRiskSettings requires auth' => [null, 'PUT', 'api/v2/growth/risk/settings', [], 401],
            'getRiskSignals requires auth' => [null, 'GET', 'api/v2/growth/risk/signals', [], 401],
            'getClassroomRiskSignals requires auth' => [null, 'GET', 'api/v2/growth/risk/groups/1', [], 401],
            'getRiskLearnerChoices requires auth' => [null, 'GET', 'api/v2/growth/risk/learners', [], 401],
            'getRiskProfessionalChoices requires auth' => [null, 'GET', 'api/v2/growth/risk/learners/1/professionals', [], 401],
            'getRiskAssignments requires auth' => [null, 'GET', 'api/v2/growth/risk/learners/1/assignments', [], 401],
            'saveRiskAssignment requires auth' => [null, 'PUT', 'api/v2/growth/risk/learners/1/assignments/1', [], 401],
            'revokeRiskAssignment requires auth' => [null, 'POST', 'api/v2/growth/risk/learners/1/assignments/1/revoke', [], 401],
            'getInterventionQueue requires auth' => [null, 'GET', 'api/v2/growth/risk/interventions', [], 401],
            'createIntervention requires auth' => [null, 'POST', 'api/v2/growth/risk/learners/1/interventions', [], 401],
            'getIntervention requires auth' => [null, 'GET', 'api/v2/growth/risk/interventions/1', [], 401],
            'updateIntervention requires auth' => [null, 'PUT', 'api/v2/growth/risk/interventions/1', [], 401],
            'getInterventionHistory requires auth' => [null, 'GET', 'api/v2/growth/risk/interventions/1/history', [], 401],
            'transferInterventionOwner requires auth' => [null, 'POST', 'api/v2/growth/risk/interventions/1/owner', [], 401],
            'revokeLearnerNoteAssignment requires auth' => [null, 'POST', 'api/v2/growth/progress/learners/1/assignments/1/revoke', [], 401],
            'getProgressConfiguration requires auth' => [null, 'GET', 'api/v2/growth/progress/configuration', [], 401],
            'saveProgressBandSettings requires auth' => [null, 'PUT', 'api/v2/growth/progress/settings', [], 401],
            'getLearnerNoteCaseload requires auth' => [null, 'GET', 'api/v2/growth/progress/caseload', [], 401],
            'getLearnerNoteAssignmentLearners requires auth' => [null, 'GET', 'api/v2/growth/progress/assignment-learners', [], 401],
            'getProgressRoster requires auth' => [null, 'GET', 'api/v2/growth/progress/groups/1/learners', [], 401],
            'getStaffProgressBand requires auth' => [null, 'GET', 'api/v2/growth/progress/groups/1/learners/1', [], 401],
            'getProgressPeriods requires auth' => [null, 'GET', 'api/v2/growth/progress/learners/1/periods', [], 401],
            'getFamilyProgressBands requires auth' => [null, 'GET', 'api/v2/growth/progress/learners/1/bands', [], 401],
            'getLearnerNotes requires auth' => [null, 'GET', 'api/v2/growth/progress/learners/1/notes', [], 401],
            'createLearnerNote requires auth' => [null, 'POST', 'api/v2/growth/progress/learners/1/notes', [], 401],
            'getLearnerNote requires auth' => [null, 'GET', 'api/v2/growth/progress/notes/1', [], 401],
            'updateLearnerNote requires auth' => [null, 'PUT', 'api/v2/growth/progress/notes/1', [], 401],
            'getLearnerNoteHistory requires auth' => [null, 'GET', 'api/v2/growth/progress/notes/1/history', [], 401],
            'getLearnerNoteAssignments requires auth' => [null, 'GET', 'api/v2/growth/progress/learners/1/assignments', [], 401],
            'saveLearnerNoteAssignment requires auth' => [null, 'PUT', 'api/v2/growth/progress/learners/1/assignments/1', [], 401],
            'mastery configuration requires auth' => [null, 'GET', 'api/v2/growth/mastery/configuration', [], 401],
            'mastery roster requires auth' => [null, 'GET', 'api/v2/growth/mastery/groups/1/learners', [], 401],
            'mastery staff learner requires auth' => [null, 'GET', 'api/v2/growth/mastery/groups/1/learners/1', [], 401],
            'mastery family subjects requires auth' => [null, 'GET', 'api/v2/growth/mastery/learners/1/subjects', [], 401],
            'mastery family learner requires auth' => [null, 'GET', 'api/v2/growth/mastery/learners/1', [], 401],
            'mastery graph read requires auth' => [null, 'GET', 'api/v2/growth/mastery/subjects/1/graph', [], 401],
            'mastery graph write requires auth' => [null, 'PUT', 'api/v2/growth/mastery/subjects/1/graph', [], 401],
            'mastery repair queue requires auth' => [null, 'POST', 'api/v2/growth/mastery/groups/1/rebuild', [], 401],
            'context without a token' => [null, 'GET', 'api/v2/me/context', [], 401],
            'context for an account with no role' => ['norole', 'GET', 'api/v2/me/context', [], 403],
        ];
    }

    #[DataProvider('scenarios')]
    public function test_v2_responses_match_the_spec(?string $who, string $method, string $path, array $body, int $status): void
    {
        if ($who !== null) {
            $this->withToken($this->tokenFor($who));
        }

        // ValidatesOpenApiResponses checks the response against openapi.yaml.
        $this->json($method, '/'.$path, $body)->assertStatus($status);
    }

    public function test_a_rate_limited_answer_matches_the_spec(): void
    {
        $token = $this->tokenFor('tara');
        for ($i = 0; $i < 60; $i++) {
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->getJson('/api/v2/me/context')->assertOk();
        }
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v2/me/context')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_the_spec_is_valid_openapi_3_1(): void
    {
        $spec = Reader::readFromYamlFile(OpenApiSpec::file());

        $this->assertTrue($spec->validate(), implode("\n", $spec->getErrors()));
        $this->assertStringStartsWith('3.1', $spec->openapi);
        $this->assertSame('/'.OpenApiSpec::BASE_PATH, $spec->servers[0]->url);
    }

    public function test_every_v2_route_is_in_the_spec_and_every_operation_is_a_route(): void
    {
        $routes = array_filter(ApiRoute::all($this->app['router']), fn (ApiRoute $route) => $route->isV2());
        $this->assertNotEmpty($routes);

        $undescribed = array_values(array_map(
            fn (ApiRoute $route) => $route->label(),
            array_filter($routes, fn (ApiRoute $route) => ! OpenApiSpec::describes($route->method, $route->uri)),
        ));
        $this->assertSame([], $undescribed, 'These v2 routes have no operation in openapi.yaml; describe them there');

        $served = array_map(fn (ApiRoute $route) => $route->method.' '.OpenApiSpec::pathFor($route->uri), $routes);
        $missing = [];
        foreach (OpenApiSpec::operations() as $path => $methods) {
            foreach ($methods as $method) {
                if (! in_array("$method $path", $served, true)) {
                    $missing[] = "$method $path";
                }
            }
        }
        $this->assertSame([], $missing, 'openapi.yaml describes operations no route serves');
    }

    public function test_every_v2_route_has_a_scenario_here(): void
    {
        $covered = array_map(fn (array $scenario) => $scenario[1].' '.OpenApiSpec::pathFor($scenario[2]), self::scenarios());
        $uncovered = [];
        foreach (ApiRoute::all($this->app['router']) as $route) {
            if ($route->isV2() && ! in_array($route->method.' '.OpenApiSpec::pathFor($route->uri), $covered, true)) {
                $uncovered[] = $route->label();
            }
        }

        $this->assertSame([], $uncovered, 'These v2 routes have no scenario in OpenApiContractTest::scenarios()');
    }

    public function test_the_dart_clients_sample_answer_matches_the_spec(): void
    {
        // packages/eschool_api's tests decode this file; both sides read one shape.
        $fixture = json_decode((string) file_get_contents(base_path('../packages/eschool_api/test/fixtures/me_context_parent.json')), true);

        OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($fixture));
    }

    public function test_a_response_that_breaks_the_spec_fails(): void
    {
        $context = $this->withToken($this->tokenFor('asha'))->getJson('/api/v2/me/context')->json();

        foreach ([
            'an undocumented field' => fn (array $body) => $body + ['surprise' => 1],
            'a missing field' => function (array $body) {
                unset($body['data']['current_school']['timezone']);

                return $body;
            },
            'a wrong type' => fn (array $body) => array_replace_recursive($body, ['data' => ['user' => ['id' => '12']]]),
        ] as $break => $change) {
            try {
                OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($change($context)));
            } catch (AssertionFailedError) {
                continue;
            }
            $this->fail("A response with $break passed the contract");
        }

        $this->expectException(AssertionFailedError::class);
        OpenApiSpec::assertResponseMatches('GET', 'api/v2/me/context', new JsonResponse($context, 418));
    }

    private function tokenFor(string $who): string
    {
        $user = match ($who) {
            'superadmin' => $this->superAdmin(),
            'staff' => $this->newUser(Role::firstOrCreate(['name' => 'Office staff', 'guard_name' => 'web'])->name),
            'norole' => $this->newUser(null),
            default => null,
        };

        return $user?->createToken('test')->plainTextToken ?? $this->school->tokens[$who];
    }

    private function newUser(?string $role): User
    {
        $user = User::forceCreate([
            'first_name' => 'Lee', 'last_name' => 'Office', 'email' => 'lee'.uniqid().'@example.com',
            'password' => Hash::make('secret123'), 'gender' => 'Female', 'mobile' => '0123456789',
        ]);
        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }
}
