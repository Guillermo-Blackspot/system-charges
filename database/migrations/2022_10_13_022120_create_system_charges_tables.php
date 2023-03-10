<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemChargesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('identified_by');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status'); //
            $table->integer('recurring_interval_count')->nullable()->default(1)->comment('Every'); // every $one week or every $two months, etc
            $table->string('recurring_interval', 15)->comment('day,week,month,year'); // week, month, year
            $table->dateTime('trial_ends_at')->nullable()->comment('Null has not trial period');
            $table->integer('expected_invoices')->nullable()->comment('Null is forever');
            $table->dateTime('billing_cycle_anchor');
            $table->dateTime('current_period_start');
            $table->dateTime('current_period_ends_at');
            $table->dateTime('cancel_at')->nullable()->comment('Null is never');            
            $table->dateTime('keep_products_active_until')->nullable()->comment('Null is forever');            
            $table->string('owner_name'); // when the owner relationship is deleted preserve the owner name in the record
            $table->string('owner_email'); // when the owner relationship is deleted preserve the owner email in the record            
            $table->json('metadata')->nullable();
            $table->nullableMorphs('owner'); // \App\Models\User
            
            $table->foreignId('service_integration_id')
                ->nullable()
                ->constrained('service_integrations')
                ->nullOnDelete();

            $table->timestamps();
        });

        Schema::create('system_payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();
            $table->boolean('paid')->nullable()->default(0);
            $table->string('amount');
            $table->string('payment_method'); //wire_t
            $table->string('status',10)->nullable(); 
            $table->dateTime('due_date')->nullable(); 
            $table->json('metadata')->nullable();            
            $table->string('subscription_identifier')->nullable(); // when the system_subscription relationship is deleted preserve the subscription identifier in the record
            $table->string('subscription_name')->nullable(); // when the system_subscription relationship is deleted preserve the subscription name in the record
            $table->string('owner_name');
            $table->string('owner_email');
            
            $table->nullableMorphs('owner');       

            $table->foreignId('system_subscription_id')
                ->nullable()
                ->constrained('system_subscriptions')
                ->nullOnDelete();

            $table->foreignId('service_integration_id')
                ->nullable()
                ->constrained('service_integrations')
                ->nullOnDelete();            

            $table->timestamps();
        });        

        Schema::create('system_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->string('quantity')->nullable()->default(1);
            $table->string('price')->nullable();
            $table->string('model_name'); // when the model relationship is deleted preserve the model name in the record
            $table->string('model_class'); // when the model relationship is deleted preserve the model class in the record
            $table->nullableMorphs('model'); // \App\Models\Programs\Program

            // if the subscription is deleted, preserve the item is not necessary
            $table->foreignId('system_subscription_id')
                ->constrained('system_subscriptions')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('system_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['service_integration_id']);
            $table->dropColumn('service_integration_id');            
        }); 

        Schema::dropIfExists('system_subscriptions');

        //
        Schema::table('system_payment_intents', function (Blueprint $table) {
            $table->dropColumn('system_subscription_id');
            $table->dropColumn('service_integration_id');

            $table->dropForeign(['system_subscription_id']);            
            $table->dropForeign(['service_integration_id']);
        });

        Schema::dropIfExists('system_payment_intents');

        //
        Schema::table('system_subscription_items', function (Blueprint $table) {
            $table->dropForeign(['system_subscription_id']);
            $table->dropColumn('system_subscription_id');
        }); 

        Schema::dropIfExists('system_subscription_items');
    }
}
