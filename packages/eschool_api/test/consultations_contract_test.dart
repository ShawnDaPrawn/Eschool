import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'anonymous answers preserve explicit null identity without response identifiers',
    () {
      final json = <String, dynamic>{
        'items': [
          {
            'respondent_user_id': null,
            'respondent_name': null,
            'answers': [
              {
                'question_id': 8,
                'option_ids': <int>[],
                'text': 'Synthetic answer',
              },
              {
                'question_id': 9,
                'option_ids': [12],
                'text': null,
              },
            ],
          },
        ],
        'page': 1,
        'last_page': 2,
      };
      final page = ConsultationResponsePage.fromJson(json);
      expect(page.items.single.respondentUserId, isNull);
      expect(page.items.single.respondentName, isNull);
      expect(page.items.single.answers.first.text, 'Synthetic answer');
      final roundtrip = ConsultationResponsePage.fromJson(page.toJson());
      expect(roundtrip.items.single.answers.last.optionIds, [12]);
      expect(roundtrip.items.single.answers.last.text, isNull);
      expect(page.items.single.toJson().containsKey('id'), isFalse);
      expect(page.items.single.toJson().containsKey('created_at'), isFalse);
    },
  );

  test(
    'aggregate results carry counts and denominators without individual text',
    () {
      final result = ConsultationResults.fromJson({
        'id': 3,
        'closed': false,
        'anonymous': true,
        'response_count': 4,
        'questions': [
          {
            'id': 8,
            'kind': 'single',
            'label': 'Synthetic preference',
            'required': true,
            'max_choices': 1,
            'options': [
              {'id': 12, 'label': 'Morning', 'statement': null},
            ],
            'answered': 3,
            'counts': [
              {'option_id': 12, 'count': 2},
            ],
          },
        ],
      });
      final roundtrip = ConsultationResults.fromJson(result.toJson());
      expect(roundtrip.responseCount, 4);
      expect(roundtrip.questions.single.answered, 3);
      expect(roundtrip.questions.single.counts.single.count, 2);
      expect(roundtrip.questions.single.toJson().containsKey('texts'), isFalse);
    },
  );

  test(
    'voting request has no caller identity or idempotency receipt reference',
    () {
      final input = ConsultationVoteInput.fromJson({
        'answers': [
          {
            'question_id': 9,
            'option_ids': [12],
          },
        ],
      });
      expect(input.toJson().keys, ['answers']);
      expect(
        ConsultationVoteInput.fromJson(
          input.toJson(),
        ).answers.single.questionId,
        9,
      );
      expect(
        ConsultationVoteReceipt.fromJson({'participated': true}).participated,
        isTrue,
      );
    },
  );

  test(
    'older facilitator profiles omit the additive consultation capability',
    () {
      final old = FacilitatorFeatures.fromJson({
        'groups': false,
        'messages': true,
        'counselling': false,
      });
      expect(old.consultations, isNull);
      expect(old.toJson().containsKey('consultations'), isFalse);
      expect(
        FacilitatorFeatures.fromJson({
          ...old.toJson(),
          'consultations': true,
        }).consultations,
        isTrue,
      );
    },
  );
}
