// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_shares_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSharesResponseData _$StrengthsSharesResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('StrengthsSharesResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['school_id', 'reader_id', 'shares']);
  final val = StrengthsSharesResponseData(
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
    readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    shares: $checkedConvert(
      'shares',
      (v) => (v as List<dynamic>)
          .map((e) => StrengthsShare.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'schoolId': 'school_id', 'readerId': 'reader_id'});

Map<String, dynamic> _$StrengthsSharesResponseDataToJson(
  StrengthsSharesResponseData instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'shares': instance.shares.map((e) => e.toJson()).toList(),
};
