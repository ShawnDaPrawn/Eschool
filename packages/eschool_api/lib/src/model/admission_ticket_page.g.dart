// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_ticket_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionTicketPage _$AdmissionTicketPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AdmissionTicketPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'actor_user_id',
            'items',
            'page',
            'last_page',
          ],
        );
        final val = AdmissionTicketPage(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          actorUserId: $checkedConvert(
            'actor_user_id',
            (v) => (v as num).toInt(),
          ),
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map((e) => AdmissionTicket.fromJson(e as Map<String, dynamic>))
                .toList(),
          ),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'actorUserId': 'actor_user_id',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$AdmissionTicketPageToJson(
  AdmissionTicketPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
};
