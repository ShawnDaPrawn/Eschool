// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_transition_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTransitionResult _$AcademicTransitionResultFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTransitionResult',
  json,
  ($checkedConvert) {
    final val = AcademicTransitionResult(
      enrolmentId: $checkedConvert('enrolment_id', (v) => (v as num?)?.toInt()),
      membershipIds: $checkedConvert(
        'membership_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      feeProrataRequested: $checkedConvert(
        'fee_prorata_requested',
        (v) => v as bool?,
      ),
      feeProrataFromOn: $checkedConvert(
        'fee_prorata_from_on',
        (v) => v as String?,
      ),
      billingPosted: $checkedConvert('billing_posted', (v) => v as bool?),
      markRule: $checkedConvert('mark_rule', (v) => v as String?),
      endedEnrolmentIds: $checkedConvert(
        'ended_enrolment_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      cancelledEnrolmentIds: $checkedConvert(
        'cancelled_enrolment_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      endedMembershipIds: $checkedConvert(
        'ended_membership_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      cancelledMembershipIds: $checkedConvert(
        'cancelled_membership_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
      recordsDeleted: $checkedConvert('records_deleted', (v) => v as bool?),
      sourceMembershipId: $checkedConvert(
        'source_membership_id',
        (v) => (v as num?)?.toInt(),
      ),
      targetMembershipId: $checkedConvert(
        'target_membership_id',
        (v) => (v as num?)?.toInt(),
      ),
      canonicalMarksReused: $checkedConvert(
        'canonical_marks_reused',
        (v) => v as bool?,
      ),
      marksCopied: $checkedConvert('marks_copied', (v) => v as bool?),
      lastAttendedOn: $checkedConvert('last_attended_on', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'enrolmentId': 'enrolment_id',
    'membershipIds': 'membership_ids',
    'feeProrataRequested': 'fee_prorata_requested',
    'feeProrataFromOn': 'fee_prorata_from_on',
    'billingPosted': 'billing_posted',
    'markRule': 'mark_rule',
    'endedEnrolmentIds': 'ended_enrolment_ids',
    'cancelledEnrolmentIds': 'cancelled_enrolment_ids',
    'endedMembershipIds': 'ended_membership_ids',
    'cancelledMembershipIds': 'cancelled_membership_ids',
    'recordsDeleted': 'records_deleted',
    'sourceMembershipId': 'source_membership_id',
    'targetMembershipId': 'target_membership_id',
    'canonicalMarksReused': 'canonical_marks_reused',
    'marksCopied': 'marks_copied',
    'lastAttendedOn': 'last_attended_on',
  },
);

Map<String, dynamic> _$AcademicTransitionResultToJson(
  AcademicTransitionResult instance,
) => <String, dynamic>{
  'enrolment_id': ?instance.enrolmentId,
  'membership_ids': ?instance.membershipIds,
  'fee_prorata_requested': ?instance.feeProrataRequested,
  'fee_prorata_from_on': ?instance.feeProrataFromOn,
  'billing_posted': ?instance.billingPosted,
  'mark_rule': ?instance.markRule,
  'ended_enrolment_ids': ?instance.endedEnrolmentIds,
  'cancelled_enrolment_ids': ?instance.cancelledEnrolmentIds,
  'ended_membership_ids': ?instance.endedMembershipIds,
  'cancelled_membership_ids': ?instance.cancelledMembershipIds,
  'records_deleted': ?instance.recordsDeleted,
  'source_membership_id': ?instance.sourceMembershipId,
  'target_membership_id': ?instance.targetMembershipId,
  'canonical_marks_reused': ?instance.canonicalMarksReused,
  'marks_copied': ?instance.marksCopied,
  'last_attended_on': ?instance.lastAttendedOn,
};
