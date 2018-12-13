<div class="wrap">
    <h1>AppSero Helper Settings</h1>

    <form method="post" action="<?php echo home_url( $_SERVER['REQUEST_URI'] ); ?>" novalidate="novalidate">

        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce( 'ashp' ); ?>">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="marketplace_type">Marketplace Type</label>
                    </th>
                    <td>
                        <select name="marketplace_type" id="marketplace_type">
                            <option value="edd" <?php $this->checked( 'edd', 'marketplace_type' ); ?> >EDD</option>
                            <option value="woosa" <?php $this->checked( 'woosa', 'marketplace_type' ); ?> >Woo SA</option>
                            <option value="wooapi" <?php $this->checked( 'wooapi', 'marketplace_type' ); ?> >Woo API</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
